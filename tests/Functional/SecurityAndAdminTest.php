<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\ContactMessage;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\UserAddress;
use App\Tests\Support\DatabaseWebTestCase;

class SecurityAndAdminTest extends DatabaseWebTestCase
{
    public function testRegisterHashesPasswordAndDoesNotAcceptElevatedRole(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->client->request('POST', '/register', [
            '_token' => $crawler->filter('input[name="_token"]')->attr('value'),
            'name' => 'New Customer', 'email' => 'NEW@example.test', 'password' => 'Long-test-password1!',
            'confirm_password' => 'Long-test-password1!', 'role' => 'admin',
        ]);
        self::assertResponseRedirects();
        $user = $this->em()->getRepository(User::class)->findOneBy(['email' => 'new@example.test']);
        self::assertNotNull($user);
        self::assertNotSame('Long-test-password1!', $user->getPassword());
        self::assertTrue(password_verify('Long-test-password1!', $user->getPassword()));
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testRegistrationRejectsMissingCsrfAndInvalidEmail(): void
    {
        $this->client->request('POST', '/register', ['name' => 'Test', 'email' => 'invalid', 'password' => 'Long-test-password1!', 'confirm_password' => 'Long-test-password1!']);
        self::assertSame(3, $this->em()->getRepository(User::class)->count([]));
        $crawler = $this->client->request('GET', '/register');
        $this->client->request('POST', '/register', ['_token' => $crawler->filter('input[name="_token"]')->attr('value'), 'name' => 'Test', 'email' => 'invalid', 'password' => 'Long-test-password1!', 'confirm_password' => 'Long-test-password1!']);
        self::assertResponseRedirects('/register');
        self::assertSame(3, $this->em()->getRepository(User::class)->count([]));
    }

    public function testRealLoginRememberMeAndProtectedLogout(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form(['_username' => 'customer@example.test', '_password' => 'Long-test-password1!', '_remember_me' => '1']);
        $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects();
        self::assertNotNull($this->client->getCookieJar()->get('REMEMBERME'));
        $crawler = $this->client->request('GET', '/account/');
        self::assertResponseIsSuccessful();
        $logout = $crawler->filter('a[href*="logout"]')->attr('href');
        self::assertStringContainsString('_csrf_token=', $logout);
        $this->client->request('GET', $logout, [], [], ['HTTP_REFERER' => 'http://localhost/account/']);
        self::assertResponseRedirects();
        $this->client->request('GET', '/account/');
        self::assertResponseRedirects('http://localhost/login');
    }

    public function testCustomerCannotEnterAdmin(): void
    {
        $this->signIn();
        foreach (['/admin/', '/admin/products/', '/admin/orders/', '/admin/users/', '/admin/messages/'] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(403);
        }
    }

    public function testAdminPagesRenderAndEscapeStoredShippingMarkup(): void
    {
        $this->signIn();
        $this->addProduct();
        $data = $this->checkoutData();
        $data['addressLine1'] = '<img src=x onerror=alert(1)>';
        $this->client->request('POST', '/checkout', $data);
        $this->signIn($this->adminId);
        foreach (['/admin/', '/admin/products/', '/admin/orders/', '/admin/users/', '/admin/messages/'] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseIsSuccessful();
        }
        $this->client->request('GET', '/admin/');
        self::assertStringNotContainsString('<img src=x onerror=alert(1)>', $this->client->getResponse()->getContent());
        self::assertStringContainsString('&lt;img', $this->client->getResponse()->getContent());
    }

    public function testAdminProductCreationValidationEditingAndDeletion(): void
    {
        $this->signIn($this->adminId);
        $crawler = $this->client->request('GET', '/admin/products/new');
        $form = $crawler->filter('form')->form(['product[name]' => 'Amber Silk', 'product[price]' => '250.00', 'product[stock]' => '5', 'product[category]' => 'Amber', 'product[status]' => 'Active']);
        $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects('/admin/products/');
        $product = $this->em()->getRepository(Product::class)->findOneBy(['name' => 'Amber Silk']);
        self::assertNotNull($product);
        $id = $product->getId();
        $crawler = $this->client->request('GET', '/admin/products/' . $id . '/edit');
        $form = $crawler->filter('form')->form(['product[stock]' => '-1', 'product[price]' => '-1']);
        $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseStatusCodeSame(422);
        self::assertSame(5, $this->em()->getConnection()->fetchOne('SELECT stock FROM products WHERE id = ?', [$id]));
        $crawler = $this->client->request('GET', '/admin/products/' . $id . '/edit');
        $this->client->submit($crawler->filter('form')->form(['product[stock]' => '8']), [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects('/admin/products/');
        self::assertSame(8, $this->em()->find(Product::class, $id)->getStock());
        $crawler = $this->client->request('GET', '/admin/products/');
        $this->client->submit($crawler->filter('form[action="/admin/products/' . $id . '"]')->form());
        self::assertNull($this->em()->find(Product::class, $id));
    }

    public function testDeletingSoldProductArchivesItAndPreservesOrderItems(): void
    {
        $this->signIn();
        $this->addProduct();
        $this->client->request('POST', '/checkout', $this->checkoutData());
        $this->signIn($this->adminId);
        $crawler = $this->client->request('GET', '/admin/products/');
        $this->client->submit($crawler->filter('form[action="/admin/products/' . $this->productId . '"]')->form());
        self::assertSame('Inactive', $this->em()->find(Product::class, $this->productId)->getStatus());
        self::assertSame(1, $this->em()->getRepository(OrderItem::class)->count([]));
    }

    public function testContactInputValidationCsrfAndMessageOwnership(): void
    {
        $this->client->jsonRequest('POST', '/contact/submit', ['name' => 'Test', 'email' => 'test@example.test', 'message' => 'Hello']);
        self::assertResponseStatusCodeSame(403);
        $this->signIn();
        $crawler = $this->client->request('GET', '/shop');
        $token = $crawler->filter('#contactForm input[name="_token"]')->attr('value');
        $this->client->jsonRequest('POST', '/contact/submit', ['_token' => $token, 'name' => [], 'email' => 'test@example.test', 'message' => 'Hello']);
        self::assertResponseStatusCodeSame(400);
        $this->client->jsonRequest('POST', '/contact/submit', ['_token' => $token, 'name' => 'Test', 'email' => 'test@example.test', 'message' => '<script>alert(1)</script>']);
        self::assertResponseIsSuccessful();
        $id = $this->em()->getRepository(ContactMessage::class)->findOneBy([])->getId();
        $this->signIn($this->otherId);
        $this->client->request('GET', '/account/messages/' . $id);
        self::assertResponseStatusCodeSame(403);
    }

    public function testSearchAndPaginationUseRealActiveProducts(): void
    {
        for ($i = 0; $i < 27; ++$i) {
            $product = (new Product())->setName('Perfume ' . $i)->setPrice('10.00')->setStock(1);
            $this->em()->persist($product);
        }
        $this->em()->persist((new Product())->setName('Hidden Rose')->setPrice('10.00')->setStock(1)->setStatus('Inactive'));
        $this->em()->flush();
        $crawler = $this->client->request('GET', '/shop');
        self::assertResponseIsSuccessful();
        self::assertCount(24, $crawler->filter('.cat-card'));
        $crawler = $this->client->request('GET', '/shop?page=2');
        self::assertCount(4, $crawler->filter('.cat-card'));
        $crawler = $this->client->request('GET', '/shop?q=Velvet&sort=price-desc');
        self::assertCount(1, $crawler->filter('.cat-card'));
        $this->client->request('GET', '/api/products/search?q=Rose');
        $products = json_decode($this->client->getResponse()->getContent(), true)['products'];
        self::assertCount(1, $products);
        self::assertSame('Velvet Rose', $products[0]['name']);
        $this->client->request('GET', '/api/products/search?q=%27%20OR%201=1%20--');
        self::assertSame([], json_decode($this->client->getResponse()->getContent(), true)['products']);
    }

    public function testCheckoutRejectsMissingCsrfAndDemoDoesNotClaimPayment(): void
    {
        $this->signIn();
        $this->addProduct();
        $data = $this->checkoutData();
        $withoutToken = $data;
        unset($withoutToken['_token']);
        $this->client->request('POST', '/checkout', $withoutToken);
        self::assertResponseStatusCodeSame(403);
        self::assertSame(0, $this->em()->getRepository(Order::class)->count([]));
        $data['payment_method'] = 'Credit Card';
        $this->client->request('POST', '/checkout', $data);
        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('#receiptModalOverlay', 'NO PAYMENT PROCESSED');
        self::assertSelectorNotExists('#cc_number');
    }
}
