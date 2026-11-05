<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Address;
use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\Product;
use App\Tests\Support\DatabaseWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CommerceTest extends DatabaseWebTestCase
{
    public function testBrowsingDoesNotCreateCartAndUsesResponsiveMetadata(): void
    {
        $this->signIn();
        $this->client->request('GET', '/shop');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html[lang="en"]');
        self::assertSelectorExists('meta[name="viewport"]');
        self::assertSame(0, $this->em()->getRepository(Cart::class)->count([]));
    }

    public function testCartRejectsMissingCsrf(): void
    {
        $this->signIn();
        $this->client->request('POST', '/api/cart/add/' . $this->productId, ['quantity' => 1]);
        self::assertResponseStatusCodeSame(403);
        self::assertSame(0, $this->em()->getRepository(Cart::class)->count([]));
    }

    #[DataProvider('invalidQuantities')]
    public function testInvalidQuantitiesCannotChangeStockOrCart(string $quantity): void
    {
        $this->signIn();
        $token = $this->cartToken();
        $this->client->request('POST', '/api/cart/add/' . $this->productId, ['quantity' => $quantity, '_token' => $token]);
        self::assertResponseStatusCodeSame(422);
        self::assertSame(10, $this->em()->find(Product::class, $this->productId)->getStock());
        self::assertSame(0, $this->em()->getRepository(Cart::class)->count([]));
    }

    public static function invalidQuantities(): array
    {
        return [['-1'], ['0'], ['11'], ['1001'], ['1.5'], ['1abc']];
    }

    public function testCartPersistsUpdatesAndPreventsDuplicateLines(): void
    {
        $this->signIn();
        $this->addProduct();
        $this->addProduct();
        $token = $this->cartToken();
        $this->client->request('POST', '/api/cart/update/' . $this->productId, ['quantity' => 3, '_token' => $token]);
        self::assertResponseIsSuccessful();
        $cart = json_decode($this->client->getResponse()->getContent(), true)['cart'];
        self::assertCount(1, $cart['items']);
        self::assertSame(3, $cart['items'][0]['quantity']);
        self::assertSame(436.05, $cart['subtotal']);
        $this->client->request('POST', '/api/cart/remove/' . $this->productId, ['_token' => $token]);
        self::assertResponseIsSuccessful();
        self::assertSame([], json_decode($this->client->getResponse()->getContent(), true)['cart']['items']);
    }

    public function testCodCheckoutIsAtomicAndIdempotentAndConfirmationClearsBag(): void
    {
        $this->signIn();
        $this->addProduct(2);
        $data = $this->checkoutData();
        $this->client->request('POST', '/checkout', $data);
        self::assertResponseRedirects();
        $receiptUrl = $this->client->getResponse()->headers->get('Location');
        self::assertSame(1, $this->em()->getRepository(Order::class)->count([]));
        self::assertSame(1, $this->em()->getRepository(Address::class)->count([]));
        $order = $this->em()->getRepository(Order::class)->findOneBy([]);
        self::assertSame('390.70', $order->getTotalAmount());
        self::assertSame('100.00', $order->getShippingFee());
        self::assertSame('Pending', $order->getStatus());
        self::assertSame(8, $this->em()->find(Product::class, $this->productId)->getStock());
        $this->client->request('POST', '/checkout', $data);
        self::assertSame(1, $this->em()->getRepository(Order::class)->count([]));
        self::assertSame(1, $this->em()->getRepository(Address::class)->count([]));
        self::assertSame(8, $this->em()->find(Product::class, $this->productId)->getStock());
        $crawler = $this->client->request('GET', $receiptUrl);
        self::assertSelectorTextContains('#receiptModalOverlay h2', 'Order Placed Successfully');
        $form = $crawler->filter('form[action="/checkout/confirm"]')->form();
        $this->client->submit($form);
        self::assertResponseRedirects('/account/?tab=orders');
        $this->client->request('GET', '/api/cart/view');
        self::assertSame([], json_decode($this->client->getResponse()->getContent(), true)['cart']['items']);
    }

    public function testOutOfStockCheckoutRollsBackOrderAndNewAddress(): void
    {
        $this->signIn();
        $this->addProduct(2);
        $data = $this->checkoutData();
        $this->em()->find(Product::class, $this->productId)->setStock(1);
        $this->em()->flush();
        $this->client->request('POST', '/checkout', $data);
        self::assertResponseRedirects('/checkout');
        self::assertSame(0, $this->em()->getRepository(Order::class)->count([]));
        self::assertSame(0, $this->em()->getRepository(Address::class)->count([]));
        self::assertSame(1, $this->em()->find(Product::class, $this->productId)->getStock());
    }

    public function testSavedAddressOwnershipAndPaymentAllowlist(): void
    {
        $this->signIn();
        $this->addProduct();
        $data = $this->checkoutData();
        $address = (new Address())->setUser($this->em()->find(\App\Entity\User::class, $this->otherId))
            ->setFullName('Other Tester')->setPhone('09123456789')->setStreetAddress('Private address');
        $this->em()->persist($address);
        $this->em()->flush();
        $data['selected_address_id'] = (string) $address->getId();
        $this->client->request('POST', '/checkout', $data);
        self::assertResponseRedirects('/checkout');
        self::assertSame(0, $this->em()->getRepository(Order::class)->count([]));
        $data['selected_address_id'] = 'new';
        $data['payment_method'] = 'Forged payment';
        $this->client->request('POST', '/checkout', $data);
        self::assertSame(0, $this->em()->getRepository(Order::class)->count([]));
    }

    public function testCustomerCannotReadAnotherCustomersOrder(): void
    {
        $this->signIn();
        $this->addProduct();
        $this->client->request('POST', '/checkout', $this->checkoutData());
        $id = $this->em()->getRepository(Order::class)->findOneBy([])->getId();
        $this->signIn($this->otherId);
        $this->client->request('GET', '/order/' . $id);
        self::assertResponseRedirects('/account/');
        $this->client->request('GET', '/checkout?order_id=' . $id);
        self::assertResponseStatusCodeSame(403);
    }

    public function testCancellationRestoresInventoryExactlyOnce(): void
    {
        $this->signIn();
        $this->addProduct(2);
        $this->client->request('POST', '/checkout', $this->checkoutData());
        $order = $this->em()->getRepository(Order::class)->findOneBy([]);
        $id = $order->getId();
        $crawler = $this->client->request('GET', '/account/?tab=orders');
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('#cancel-form-' . $id . ' input[name="_token"]')->attr('value');
        $this->client->request('POST', '/account/order/' . $id . '/cancel', ['_token' => $token]);
        self::assertSame(10, $this->em()->find(Product::class, $this->productId)->getStock());
        $this->client->request('POST', '/account/order/' . $id . '/cancel', ['_token' => $token]);
        self::assertSame(10, $this->em()->find(Product::class, $this->productId)->getStock());
        self::assertSame('Cancelled', $this->em()->find(Order::class, $id)->getStatus());
    }
}
