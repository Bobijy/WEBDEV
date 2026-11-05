<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Address;
use App\Entity\ContactMessage;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\UserAddress;
use App\Tests\Support\DatabaseWebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AccountAndEdgeCaseTest extends DatabaseWebTestCase
{
    private function addressData(): array
    {
        return ['firstName' => 'Customer', 'lastName' => 'Tester', 'phone' => '09123456789', 'addressLine1' => '10 Test Street', 'barangay' => 'Test', 'city' => 'Manila', 'region' => 'Metro Manila', 'country' => 'Philippines', 'postalCode' => '1000'];
    }

    public function testAddressBookCreationEditingDefaultAndOwnership(): void
    {
        $this->signIn();
        $crawler = $this->client->request('GET', '/account/');
        $token = $crawler->filter('#addressModalOverlay input[name="_token"]')->attr('value');
        $this->client->request('POST', '/account/address/update', $this->addressData() + ['_token' => $token, 'address_id' => '']);
        self::assertResponseRedirects('/account/?tab=addresses');
        $address = $this->em()->getRepository(Address::class)->findOneBy([]);
        self::assertNotNull($address);
        self::assertTrue($address->isDefault());
        $id = $address->getId();
        $data = $this->addressData();
        $data['addressLine1'] = '20 New Street';
        $this->client->request('POST', '/account/address/update', $data + ['_token' => $token, 'address_id' => $id]);
        self::assertSame('20 New Street', $this->em()->find(Address::class, $id)->getAddressLine1());
        self::assertStringContainsString('20 New Street', $this->em()->find(Address::class, $id)->getFullAddressText());
        $this->signIn($this->otherId);
        $crawler = $this->client->request('GET', '/account/');
        $token = $crawler->filter('#addressModalOverlay input[name="_token"]')->attr('value');
        $this->client->request('POST', '/account/address/update', $data + ['_token' => $token, 'address_id' => $id]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testProfileFormIsNotNestedAndRejectsDuplicateEmail(): void
    {
        $this->signIn();
        $crawler = $this->client->request('GET', '/account/');
        self::assertCount(1, $crawler->filter('#inlineProfileForm'));
        self::assertCount(0, $crawler->filter('form form'));
        $form = $crawler->filter('#inlineProfileForm')->form(['profile[email]' => 'Other@Example.Test']);
        $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertSame('customer@example.test', $this->em()->getConnection()->fetchOne('SELECT email FROM users WHERE id = ?', [$this->customerId]));
        self::assertResponseStatusCodeSame(422);
        $crawler = $this->client->request('GET', '/account/');
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->filter('#inlineProfileForm')->form([
            'profile[full_name]' => 'Updated Tester',
            'profile[email]' => 'CUSTOMER@EXAMPLE.TEST',
            'profile[phone]' => '09123456789',
        ]), [], ['HTTP_ORIGIN' => 'http://localhost']);
        $errors = $this->client->getCrawler()->filter('#inlineProfileForm ul')->each(fn ($node) => $node->text());
        self::assertResponseRedirects('/account/?tab=profile', 302, implode(' ', $errors));
        self::assertSame('Updated Tester', $this->em()->find(User::class, $this->customerId)->getFullName());
        self::assertSame('09123456789', $this->em()->find(User::class, $this->customerId)->getPhone());
    }

    public function testPasswordPolicyAndUpdateUseCurrentPasswordAndCsrf(): void
    {
        $this->signIn();
        $crawler = $this->client->request('GET', '/account/');
        $token = $crawler->filter('form[action="/account/password"] input[name="_token"]')->attr('value');
        $before = $this->em()->find(User::class, $this->customerId)->getPassword();
        $this->client->request('POST', '/account/password', ['_token' => $token, 'current_password' => 'Long-test-password1!', 'new_password' => 'tiny', 'confirm_password' => 'tiny']);
        self::assertSame($before, $this->em()->find(User::class, $this->customerId)->getPassword());
        $this->client->request('POST', '/account/password', ['_token' => $token, 'current_password' => 'Long-test-password1!', 'new_password' => 'Updated-test-password2!', 'confirm_password' => 'Updated-test-password2!']);
        self::assertTrue(password_verify('Updated-test-password2!', $this->em()->find(User::class, $this->customerId)->getPassword()));
    }

    public function testLoginThrottlesRepeatedBadPasswords(): void
    {
        for ($attempt = 0; $attempt < 6; ++$attempt) {
            $crawler = $this->client->request('GET', '/login');
            $this->client->submit($crawler->filter('form')->form(['_username' => 'customer@example.test', '_password' => 'wrong-password']), [], ['HTTP_ORIGIN' => 'http://localhost', 'REMOTE_ADDR' => '192.0.2.77']);
        }
        $this->client->request('GET', '/login');
        self::assertSelectorTextContains('.account-msg', 'Too many failed login attempts');
    }

    public function testGuestCartAndInvalidMethodsAndReceiptEligibility(): void
    {
        $this->client->request('POST', '/api/cart/add/' . $this->productId, ['quantity' => 1]);
        self::assertResponseStatusCodeSame(401);
        $this->client->request('GET', '/api/cart/add/' . $this->productId);
        self::assertResponseStatusCodeSame(405);
        $this->signIn();
        $this->addProduct();
        $this->client->request('POST', '/checkout', $this->checkoutData());
        $order = $this->em()->getRepository(Order::class)->findOneBy([]);
        $id = $order->getId();
        $this->client->request('GET', '/order/' . $id . '/receipt');
        self::assertResponseRedirects('/order/' . $id);
        $this->em()->find(Order::class, $id)->setStatus('Completed');
        $this->em()->flush();
        $this->client->request('GET', '/order/' . $id . '/receipt');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('10 Test Street', $this->client->getResponse()->getContent());
    }

    public function testUnsafeUploadIsRejectedAndValidImageCanBeUploaded(): void
    {
        $this->signIn($this->adminId);
        $crawler = $this->client->request('GET', '/admin/products/new');
        $values = $crawler->filter('form')->form(['product[name]' => 'Uploaded Perfume', 'product[price]' => '100.00', 'product[stock]' => '1'])->getPhpValues();
        $directory = dirname(__DIR__, 2) . '/var/qa';
        $bad = $directory . '/unsafe-upload.php';
        file_put_contents($bad, '<?php echo "test";');
        $this->client->request('POST', '/admin/products/new', $values, ['product' => ['imageFile' => new UploadedFile($bad, 'unsafe.php', 'application/x-php', null, true)]], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseStatusCodeSame(422);
        self::assertNull($this->em()->getRepository(Product::class)->findOneBy(['name' => 'Uploaded Perfume']));
        $crawler = $this->client->request('GET', '/admin/products/new');
        $values = $crawler->filter('form')->form(['product[name]' => 'Uploaded Perfume', 'product[price]' => '100.00', 'product[stock]' => '1'])->getPhpValues();
        $image = $directory . '/valid-upload.png';
        copy(dirname(__DIR__, 2) . '/public/assets/images/pour-homme.png', $image);
        $this->client->request('POST', '/admin/products/new', $values, ['product' => ['imageFile' => new UploadedFile($image, 'valid.png', 'image/png', null, true)]], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects('/admin/products/');
        $product = $this->em()->getRepository(Product::class)->findOneBy(['name' => 'Uploaded Perfume']);
        self::assertNotNull($product);
        self::assertStringStartsWith('uploads/valid-', $product->getImage());
        $uploaded = dirname(__DIR__, 2) . '/public/' . $product->getImage();
        self::assertFileExists($uploaded);
        unlink($uploaded);
        if (is_file($bad)) {
            unlink($bad);
        }
    }

    public function testReplyOwnershipCsrfAndAdminStatusCannotResurrectCancellation(): void
    {
        $message = (new ContactMessage())->setName('Customer')->setEmail('customer@example.test')->setMessage('Help')
            ->setUser($this->em()->find(User::class, $this->customerId));
        $this->em()->persist($message);
        $this->em()->flush();
        $id = $message->getId();
        $this->signIn();
        $crawler = $this->client->request('GET', '/account/messages/' . $id);
        $this->client->submit($crawler->filter('form[action="/account/messages/' . $id . '"]')->form(['reply_message' => 'Follow up']));
        self::assertSame(1, $this->em()->getRepository(\App\Entity\ContactReply::class)->count([]));
        $this->addProduct();
        $this->client->request('POST', '/checkout', $this->checkoutData());
        $order = $this->em()->getRepository(Order::class)->findOneBy([]);
        $order->setStatus('Cancelled');
        $this->em()->flush();
        $orderId = $order->getId();
        $this->signIn($this->adminId);
        $crawler = $this->client->request('GET', '/admin/orders/' . $orderId . '/edit');
        $values = $crawler->filter('#orderForm')->form()->getPhpValues();
        $values['status'] = 'Completed';
        $this->client->request('POST', '/admin/orders/' . $orderId . '/status', $values);
        self::assertResponseRedirects('/admin/orders/');
        self::assertSame('Cancelled', $this->em()->find(Order::class, $orderId)->getStatus());
    }
}
