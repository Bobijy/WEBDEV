<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Tests\Support\DatabaseWebTestCase;

class BusinessInvariantTest extends DatabaseWebTestCase
{
    public function testLaterStockFailureRollsBackEarlierProductDeduction(): void
    {
        $this->signIn();
        $this->addProduct();
        $second = (new Product())->setName('Amber Silk')->setPrice('200.00')->setStock(5);
        $this->em()->persist($second);
        $this->em()->flush();
        $secondId = $second->getId();
        $this->client->request('POST', '/api/cart/add/' . $secondId, ['quantity' => 2, '_token' => $this->cartToken()]);
        self::assertResponseIsSuccessful();
        $data = $this->checkoutData();
        $this->em()->find(Product::class, $secondId)->setStock(1);
        $this->em()->flush();
        $this->client->request('POST', '/checkout', $data);
        self::assertResponseRedirects('/checkout');
        self::assertSame(10, $this->em()->find(Product::class, $this->productId)->getStock());
        self::assertSame(1, $this->em()->find(Product::class, $secondId)->getStock());
        self::assertSame(0, $this->em()->getRepository(Order::class)->count([]));
        self::assertSame(0, $this->em()->getRepository(Address::class)->count([]));
    }

    public function testConfirmationRetainsItemsAddedAfterCheckoutAndCannotRepeat(): void
    {
        $this->signIn();
        $this->addProduct(2);
        $this->client->request('POST', '/checkout', $this->checkoutData());
        $receiptUrl = $this->client->getResponse()->headers->get('Location');
        $this->addProduct();
        $second = (new Product())->setName('Amber Silk')->setPrice('200.00')->setStock(5);
        $this->em()->persist($second);
        $this->em()->flush();
        $this->client->request('POST', '/api/cart/add/' . $second->getId(), ['quantity' => 2, '_token' => $this->cartToken()]);
        $crawler = $this->client->request('GET', $receiptUrl);
        $confirmation = $crawler->filter('form[action="/checkout/confirm"]')->form()->getPhpValues();
        $this->client->request('POST', '/checkout/confirm', $confirmation);
        self::assertResponseRedirects('/account/?tab=orders');
        $this->client->request('GET', '/api/cart/view');
        $items = json_decode($this->client->getResponse()->getContent(), true)['cart']['items'];
        self::assertCount(2, $items);
        self::assertSame(1, $items[0]['quantity']);
        self::assertSame(2, $items[1]['quantity']);
        $this->client->request('POST', '/checkout/confirm', $confirmation);
        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCannotDeleteCustomerWithOrderHistory(): void
    {
        $this->signIn();
        $this->addProduct();
        $this->client->request('POST', '/checkout', $this->checkoutData());
        $this->signIn($this->adminId);
        $crawler = $this->client->request('GET', '/admin/users/');
        $form = $crawler->filter('form[action="/admin/users/' . $this->customerId . '"]')->form();
        $this->client->submit($form);
        self::assertResponseRedirects('/admin/users/');
        self::assertNotNull($this->em()->find(User::class, $this->customerId));
        self::assertSame(1, $this->em()->getRepository(Order::class)->count([]));
    }

    public function testOrderStatusMovesForwardAndCancelledOrderDisplaysCancellation(): void
    {
        $this->signIn();
        $this->addProduct();
        $this->client->request('POST', '/checkout', $this->checkoutData());
        $orderId = $this->em()->getRepository(Order::class)->findOneBy([])->getId();
        $this->signIn($this->adminId);
        $crawler = $this->client->request('GET', '/admin/orders/' . $orderId . '/edit');
        $this->client->submit($crawler->filter('#orderForm')->form(['status' => 'Approved']));
        $crawler = $this->client->request('GET', '/admin/orders/' . $orderId . '/edit');
        $this->client->submit($crawler->filter('#orderForm')->form(['status' => 'Processing']));
        $crawler = $this->client->request('GET', '/admin/orders/' . $orderId . '/edit');
        $this->client->submit($crawler->filter('#orderForm')->form(['status' => 'Shipped']));
        self::assertSame('Shipped', $this->em()->find(Order::class, $orderId)->getStatus());
        $crawler = $this->client->request('GET', '/admin/orders/' . $orderId . '/edit');
        $data = $crawler->filter('#orderForm')->form()->getPhpValues();
        $data['status'] = 'Pending';
        $this->client->request('POST', '/admin/orders/' . $orderId . '/status', $data);
        self::assertResponseRedirects('/admin/orders/');
        self::assertSame('Shipped', $this->em()->find(Order::class, $orderId)->getStatus());
        $data['status'] = 'Cancelled';
        $this->client->request('POST', '/admin/orders/' . $orderId . '/status', $data);
        self::assertSame('Shipped', $this->em()->find(Order::class, $orderId)->getStatus());
        self::assertSame(9, $this->em()->find(Product::class, $this->productId)->getStock());
        $this->client->request('GET', '/admin/orders/'); // Fresh manager after the rejected transaction.
        $this->em()->find(Order::class, $orderId)->setStatus('Cancelled');
        $this->em()->flush();
        $this->signIn();
        $this->client->request('GET', '/order/' . $orderId);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.status-info', 'cancelled');
        self::assertStringNotContainsString('Payment processed', $this->client->getResponse()->getContent());
    }

    public function testCheckoutRefreshesPreloadedCartQuantitiesAfterLock(): void
    {
        $this->signIn();
        $this->addProduct(2);
        $data = $this->checkoutData(); // Hydrates the cart before the transaction.
        $this->em()->getConnection()->executeStatement(
            'UPDATE cart_items SET quantity = 3 WHERE product_id = ?',
            [$this->productId],
        );
        $order = static::getContainer()->get(\App\Service\OrderService::class)->processCheckout(
            ['shipping_address' => '10 Test Street, Manila', 'payment_method' => 'Cash On Delivery'],
            $data['checkout_token'],
        );
        self::assertSame(3, $order->getOrderItems()->first()->getQuantity());
        self::assertSame('536.05', $order->getTotalAmount());
        self::assertSame(7, $this->em()->find(Product::class, $this->productId)->getStock());
    }

    public function testCheckoutIgnoresClientPricesShippingDiscountsAndAddressText(): void
    {
        $this->signIn();
        $this->addProduct();
        $data = $this->checkoutData() + [
            'total_amount' => '0.01', 'shipping_fee' => '0.00',
            'discount_amount' => '99999.00', 'shipping_address' => 'Forged address',
        ];
        $this->client->request('POST', '/checkout', $data);
        self::assertResponseRedirects();
        $order = $this->em()->getRepository(Order::class)->findOneBy([]);
        self::assertSame('245.35', $order->getTotalAmount());
        self::assertSame('100.00', $order->getShippingFee());
        self::assertSame('0.00', $order->getDiscountAmount());
        self::assertStringContainsString('10 Test Street', $order->getShippingAddress());
    }

    public function testAdminCancellationBeforeShippingRestoresStockOnlyOnce(): void
    {
        $this->signIn();
        $this->addProduct(2);
        $this->client->request('POST', '/checkout', $this->checkoutData());
        $orderId = $this->em()->getRepository(Order::class)->findOneBy([])->getId();
        $this->signIn($this->adminId);
        foreach (['Approved', 'Processing', 'Cancelled'] as $status) {
            $crawler = $this->client->request('GET', '/admin/orders/' . $orderId . '/edit');
            $this->client->submit($crawler->filter('#orderForm')->form(['status' => $status]));
            self::assertResponseRedirects('/admin/orders/');
            self::assertSame($status, $this->em()->find(Order::class, $orderId)->getStatus());
        }
        self::assertSame(10, $this->em()->find(Product::class, $this->productId)->getStock());
        $crawler = $this->client->request('GET', '/admin/orders/' . $orderId . '/edit');
        $this->client->submit($crawler->filter('#orderForm')->form(['status' => 'Cancelled']));
        self::assertSame(10, $this->em()->find(Product::class, $this->productId)->getStock());
    }
}
