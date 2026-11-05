<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminAccessDeniedTest extends WebTestCase
{
    #[DataProvider('debugModes')]
    public function testCustomerGetsAModalWithoutAdminContentOrDebugDetails(bool $debug): void
    {
        $client = static::createClient(['debug' => $debug]);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $em->getConnection();
        if (!$connection->getDatabasePlatform() instanceof SQLitePlatform || !str_contains(str_replace('\\', '/', $connection->getParams()['path'] ?? ''), '/var/qa/test')) {
            throw new \LogicException('This test requires the isolated test database.');
        }
        $schema = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
        $customer = (new User())->setFullName('Customer Tester')->setEmail('customer@example.test')->setPassword('unused-test-password');
        $admin = (new User())->setFullName('Admin Tester')->setEmail('admin@example.test')->setPassword('unused-test-password')->setRole('admin');
        $em->persist($customer);
        $em->persist($admin);
        $em->flush();
        $adminId = $admin->getId();
        $client->loginUser($customer);

        foreach (['/admin', '/admin/', '/admin/products/', '/admin/orders/', '/admin/users/', '/admin/messages/'] as $path) {
            $client->request('GET', $path);
            self::assertResponseStatusCodeSame(403);
            self::assertSelectorExists('#flashModalBackdrop[role="dialog"][data-return-url="/"]');
            self::assertSelectorTextContains('#flashModalActionBtn', 'Back to store');
            self::assertStringContainsString('This area is reserved for administrators.', $client->getResponse()->getContent());
            self::assertStringNotContainsString('ROLE_ADMIN', $client->getResponse()->getContent());
            self::assertSelectorNotExists('.admin-sidebar');
        }

        $client->request('GET', '/admin/', [], [], ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseStatusCodeSame(403);
        self::assertSelectorNotExists('#flashModalBackdrop');

        $client->loginUser(static::getContainer()->get(EntityManagerInterface::class)->find(User::class, $adminId));
        $client->request('GET', '/admin/');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.admin-sidebar');

        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#flashModalActionBtn', 'Dismiss');
        self::assertSelectorExists('#flashModalBackdrop[data-return-url=""]');
    }

    public static function debugModes(): array
    {
        return ['normal' => [false], 'development' => [true]];
    }

    public function testGuestsStillGoToSignIn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/');
        self::assertResponseRedirects('http://localhost/login');
    }
}
