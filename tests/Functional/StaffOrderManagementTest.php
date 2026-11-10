<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Tests\Support\DatabaseWebTestCase;

class StaffOrderManagementTest extends DatabaseWebTestCase
{
    private function placeCustomerOrderAndSignInAsStaff(): int
    {
        $this->signIn();
        $this->addProduct(2);
        $this->client->request('POST', '/checkout', $this->checkoutData());
        self::assertResponseRedirects();
        $id = $this->em()->getRepository(Order::class)->findOneBy([])->getId();
        $staff = (new User())->setFullName('Order Staff')->setEmail('order-staff@example.test')
            ->setRole('staff')->setPassword(password_hash('Long-test-password1!', PASSWORD_BCRYPT));
        $this->em()->persist($staff);
        $this->em()->flush();
        $this->signIn($staff->getId());
        return $id;
    }

    public function testStaffCanViewFilterAndFulfilCustomerOrdersWithoutAdminAccess(): void
    {
        $id = $this->placeCustomerOrderAndSignInAsStaff();
        $this->client->request('GET', '/staff/orders/');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="/staff/orders/"]');
        self::assertSelectorExists('a[href="/staff/orders/' . $id . '/edit"]');
        self::assertSelectorNotExists('a[href="/admin/users/"]');
        self::assertSelectorNotExists('a[href="/admin/orders/"]');
        self::assertSelectorTextContains('#ordersTable', 'CUSTOMER TESTER');
        $this->client->request('GET', '/staff/orders/?status=Completed');
        self::assertSelectorTextContains('#ordersTable', 'No orders found.');
        foreach (['Approved', 'Processing', 'Shipped', 'Completed'] as $status) {
            $crawler = $this->client->request('GET', '/staff/orders/' . $id . '/edit');
            self::assertResponseIsSuccessful();
            self::assertSelectorExists('#orderForm[action="/staff/orders/' . $id . '/status"]');
            self::assertSelectorExists('a[href="/staff/orders/"]');
            $this->client->submit($crawler->filter('#orderForm')->form(['status' => $status]));
            self::assertResponseRedirects('/staff/orders/');
            self::assertSame($status, $this->em()->find(Order::class, $id)->getStatus());
        }
        $this->client->request('GET', '/staff/orders/' . $id . '/receipt');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('CUSTOMER TESTER', $this->client->getResponse()->getContent());
        foreach (['/admin/', '/admin/users/', '/admin/orders/', '/admin/orders/' . $id . '/edit'] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(403);
        }
    }

    public function testStaffCancellationRestoresStockOnceAndCannotReverseOrCancelShippedOrders(): void
    {
        $id = $this->placeCustomerOrderAndSignInAsStaff();
        $crawler = $this->client->request('GET', '/staff/orders/' . $id . '/edit');
        self::assertResponseIsSuccessful();
        $data = $crawler->filter('#orderForm')->form(['status' => 'Cancelled'])->getPhpValues();
        $this->client->request('POST', '/staff/orders/' . $id . '/status', $data);
        self::assertResponseRedirects('/staff/orders/');
        self::assertSame('Cancelled', $this->em()->find(Order::class, $id)->getStatus());
        self::assertSame(10, $this->em()->find(Product::class, $this->productId)->getStock());
        $this->client->request('POST', '/staff/orders/' . $id . '/status', $data);
        self::assertSame(10, $this->em()->find(Product::class, $this->productId)->getStock());
        $data['status'] = 'Approved';
        $this->client->request('POST', '/staff/orders/' . $id . '/status', $data);
        self::assertSame('Cancelled', $this->em()->find(Order::class, $id)->getStatus());
        $this->client->request('GET', '/staff/orders/');
        $this->em()->find(Order::class, $id)->setStatus('Shipped');
        $this->em()->flush();
        $crawler = $this->client->request('GET', '/staff/orders/' . $id . '/edit');
        $data = $crawler->filter('#orderForm')->form()->getPhpValues();
        foreach (['Pending', 'Cancelled'] as $status) {
            $data['status'] = $status;
            $this->client->request('POST', '/staff/orders/' . $id . '/status', $data);
            self::assertSame('Shipped', $this->em()->find(Order::class, $id)->getStatus());
            self::assertSame(10, $this->em()->find(Product::class, $this->productId)->getStock());
        }
    }

    public function testOrderWritesRequireCsrfAndCustomersCannotUseStaffRoutes(): void
    {
        $id = $this->placeCustomerOrderAndSignInAsStaff();
        $this->client->request('POST', '/staff/orders/' . $id . '/status', ['status' => 'Approved']);
        self::assertResponseRedirects('/staff/orders/');
        self::assertSame('Pending', $this->em()->find(Order::class, $id)->getStatus());
        $crawler = $this->client->request('GET', '/staff/orders/' . $id . '/edit');
        $data = $crawler->filter('#orderForm')->form()->getPhpValues();
        $data['status'] = 'Unknown';
        $this->client->request('POST', '/staff/orders/' . $id . '/status', $data);
        self::assertSame('Pending', $this->em()->find(Order::class, $id)->getStatus());
        $this->signIn();
        foreach (['/staff/orders/', '/staff/orders/' . $id . '/edit', '/staff/orders/' . $id . '/receipt'] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(403);
        }
        $data['status'] = 'Approved';
        $this->client->request('POST', '/staff/orders/' . $id . '/status', $data);
        self::assertResponseStatusCodeSame(403);
        self::assertSame('Pending', $this->em()->find(Order::class, $id)->getStatus());
    }
}
