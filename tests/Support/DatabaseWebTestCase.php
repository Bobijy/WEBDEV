<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected int $customerId;
    protected int $otherId;
    protected int $adminId;
    protected int $productId;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $em = $this->em();
        $connection = $em->getConnection();
        if (!$connection->getDatabasePlatform() instanceof SQLitePlatform || !str_contains(str_replace('\\', '/', $connection->getParams()['path'] ?? ''), '/var/qa/test')) {
            throw new \LogicException('Tests may only reset the isolated var/qa/test SQLite database.');
        }
        $connection->executeStatement('PRAGMA foreign_keys = ON');
        $schema = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $hasher = static::getContainer()->get('security.user_password_hasher');
        $users = [];
        foreach (['customer', 'other', 'admin'] as $name) {
            $user = (new User())->setFullName(ucfirst($name) . ' Tester')->setEmail($name . '@example.test');
            $user->setPassword($hasher->hashPassword($user, 'Long-test-password1!'));
            if ($name === 'admin') {
                $user->setRole('admin');
            }
            $em->persist($user);
            $users[$name] = $user;
        }
        $floral = (new Category())->setName('Floral');
        $amber = (new Category())->setName('Amber');
        $em->persist($floral);
        $em->persist($amber);
        $product = (new Product())->setName('Velvet Rose')->setPrice('145.35')->setStock(10)->setCategory($floral)
            ->setImage('assets/images/pour-homme.png');
        $em->persist($product);
        $em->flush();
        $this->customerId = $users['customer']->getId();
        $this->otherId = $users['other']->getId();
        $this->adminId = $users['admin']->getId();
        $this->productId = $product->getId();
    }

    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function signIn(?int $id = null): void
    {
        $this->client->loginUser($this->em()->find(User::class, $id ?? $this->customerId));
    }

    protected function cartToken(): string
    {
        return (string) $this->client->request('GET', '/shop')->filter('meta[name="cart-token"]')->attr('content');
    }

    protected function addProduct(int $quantity = 1): void
    {
        $token = $this->cartToken();
        $this->client->request('POST', '/api/cart/add/' . $this->productId, ['quantity' => $quantity, '_token' => $token]);
        self::assertResponseIsSuccessful();
    }

    protected function checkoutData(): array
    {
        $crawler = $this->client->request('GET', '/checkout');
        self::assertResponseIsSuccessful();

        return [
            '_token' => $crawler->filter('#checkoutForm input[name="_token"]')->attr('value'),
            'checkout_token' => $crawler->filter('input[name="checkout_token"]')->attr('value'),
            'selected_address_id' => 'new', 'payment_method' => 'Cash On Delivery',
            'firstName' => 'Customer', 'lastName' => 'Tester', 'phone' => '09123456789',
            'addressLine1' => '10 Test Street', 'barangay' => 'Test Barangay', 'city' => 'Manila',
            'region' => 'Metro Manila', 'country' => 'Philippines', 'postalCode' => '1000',
        ];
    }
}
