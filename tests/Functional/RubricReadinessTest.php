<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Product;
use App\Entity\User;
use App\Tests\Support\DatabaseWebTestCase;

class RubricReadinessTest extends DatabaseWebTestCase
{
    private function makeStaff(string $email = 'staff@example.test'): int
    {
        $user = (new User())->setFullName('Staff Tester')->setEmail($email)->setRole('staff')
            ->setPassword(password_hash('Long-test-password1!', PASSWORD_BCRYPT));
        $this->em()->persist($user);
        $this->em()->flush();
        return $user->getId();
    }

    public function testStaffCanCreateEditAndDeleteOwnProductsButNotEnterAdmin(): void
    {
        $staffId = $this->makeStaff();
        $this->signIn($staffId);
        $crawler = $this->client->request('GET', '/staff/products/');
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('a[href="/admin/users/"]');
        self::assertSelectorExists('a[href="/account/"]');
        self::assertStringContainsString('Velvet Rose', $crawler->filter('#productsTable')->text());
        $crawler = $this->client->request('GET', '/staff/products/new');
        $this->client->submit($crawler->filter('form')->form([
            'product[name]' => 'Staff Fragrance', 'product[price]' => '200.00', 'product[stock]' => '4',
        ]), [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects('/staff/products/');
        $product = $this->em()->getRepository(Product::class)->findOneBy(['name' => 'Staff Fragrance']);
        self::assertSame($staffId, $product->getCreatedBy()->getId());
        $id = $product->getId();
        $crawler = $this->client->request('GET', '/staff/products/' . $id . '/edit');
        $this->client->submit($crawler->filter('form')->form(['product[name]' => 'Updated Fragrance']), [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects('/staff/products/');
        self::assertSame('Updated Fragrance', $this->em()->find(Product::class, $id)->getName());
        $crawler = $this->client->request('GET', '/staff/products/');
        self::assertSelectorExists('form[data-confirm]');
        self::assertSelectorExists('script[src*="management.js"]');
        $this->client->submit($crawler->filter('form[action="/staff/products/' . $id . '"]')->form());
        self::assertNull($this->em()->find(Product::class, $id));
        foreach (['/admin/', '/admin/users/', '/admin/users/new', '/admin/users/' . $this->customerId . '/edit', '/admin/products/'] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(403);
        }
        $this->client->request('POST', '/admin/users/' . $this->customerId);
        self::assertResponseStatusCodeSame(403);
        self::assertNotNull($this->em()->find(User::class, $this->customerId));
    }

    public function testStaffSeeAllProductsButCannotChangeOtherStaffAdminOrUnownedProducts(): void
    {
        $staffId = $this->makeStaff();
        $otherStaffId = $this->makeStaff('second-staff@example.test');
        $ids = [$this->productId];
        foreach ([$otherStaffId, $this->adminId] as $ownerId) {
            $product = (new Product())->setName('Shared product')->setPrice('100.00')
                ->setCreatedBy($this->em()->find(User::class, $ownerId));
            $this->em()->persist($product);
            $this->em()->flush();
            $ids[] = $product->getId();
        }
        $this->signIn($otherStaffId);
        $this->client->request('GET', '/staff/products/');
        self::assertResponseIsSuccessful();
        self::assertCount(3, $this->client->getCrawler()->filter('#productsTableBody tr'));
        self::assertSelectorExists('a[href="/staff/products/' . $ids[1] . '/edit"]');
        self::assertSelectorNotExists('a[href="/staff/products/' . $ids[2] . '/edit"]');
        $this->signIn($staffId);
        foreach ($ids as $id) {
            $crawler = $this->client->request('GET', '/staff/products/');
            self::assertCount(3, $crawler->filter('#productsTableBody tr'));
            self::assertSelectorNotExists('a[href="/staff/products/' . $id . '/edit"]');
            self::assertSelectorNotExists('form[action="/staff/products/' . $id . '"]');
            $creatorId = $this->em()->find(Product::class, $id)->getCreatedBy()?->getId();
            $name = $this->em()->find(Product::class, $id)->getName();
            foreach (['GET', 'POST'] as $method) {
                $this->client->request($method, '/staff/products/' . $id . '/edit', ['product' => ['name' => 'Forbidden update', 'createdBy' => $staffId]]);
                self::assertResponseStatusCodeSame(403);
            }
            $this->client->request('POST', '/staff/products/' . $id, ['createdBy' => $staffId]);
            self::assertResponseStatusCodeSame(403);
            self::assertNotNull($this->em()->find(Product::class, $id));
            self::assertSame($name, $this->em()->find(Product::class, $id)->getName());
            self::assertSame($creatorId, $this->em()->find(Product::class, $id)->getCreatedBy()?->getId());
        }
        $this->signIn($this->adminId);
        foreach ($ids as $id) {
            $this->client->request('GET', '/admin/products/' . $id . '/edit');
            self::assertResponseIsSuccessful();
        }
        $this->signIn();
        $this->client->request('GET', '/staff/products/');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/staff/products/new', ['product' => ['name' => 'Forged']]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanCreateAndEditStaffResetPasswordsAndFilterAccounts(): void
    {
        $this->signIn($this->adminId);
        $crawler = $this->client->request('GET', '/admin/users/new');
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->filter('#userAccountForm')->form([
            'user_account[fullName]' => 'New Staff', 'user_account[email]' => 'NEWSTAFF@example.test',
            'user_account[role]' => 'staff', 'user_account[plainPassword]' => 'New-staff-password1!',
        ]), [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects('/admin/users/');
        $user = $this->em()->getRepository(User::class)->findOneBy(['email' => 'newstaff@example.test']);
        self::assertNotNull($user);
        self::assertContains('ROLE_STAFF', $user->getRoles());
        self::assertTrue(password_verify('New-staff-password1!', $user->getPassword()));
        $id = $user->getId();
        $crawler = $this->client->request('GET', '/admin/users/' . $id . '/edit');
        $this->client->submit($crawler->filter('#userAccountForm')->form([
            'user_account[fullName]' => 'Updated Staff', 'user_account[email]' => 'updatedstaff@example.test',
            'user_account[role]' => 'admin', 'user_account[plainPassword]' => 'Reset-staff-password2!',
        ]), [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects('/admin/users/');
        $user = $this->em()->find(User::class, $id);
        self::assertSame('Updated Staff', $user->getFullName());
        self::assertSame('admin', $user->getRole());
        self::assertTrue(password_verify('Reset-staff-password2!', $user->getPassword()));
        $this->client->request('GET', '/admin/users/?role=staff');
        self::assertSelectorTextNotContains('#usersTable', 'updatedstaff@example.test');
        $this->client->request('GET', '/admin/users/?role=admin&q=Updated');
        self::assertSelectorTextContains('#usersTable', 'updatedstaff@example.test');
    }

    public function testInvalidAccountFormsAndCsrfCannotMutateUsersOrGrantRoles(): void
    {
        $this->signIn($this->adminId);
        $crawler = $this->client->request('GET', '/admin/users/new');
        $this->client->submit($crawler->filter('#userAccountForm')->form([
            'user_account[fullName]' => 'Invalid', 'user_account[email]' => 'not-an-email',
            'user_account[plainPassword]' => 'short',
        ]), [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseStatusCodeSame(422);
        self::assertSame(3, $this->em()->getRepository(User::class)->count([]));
        $crawler = $this->client->request('GET', '/admin/users/' . $this->customerId . '/edit');
        $this->client->submit($crawler->filter('#userAccountForm')->form([
            'user_account[email]' => 'ADMIN@EXAMPLE.TEST', 'user_account[role]' => 'admin',
        ]), [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseStatusCodeSame(422);
        self::assertSame('customer', $this->em()->find(User::class, $this->customerId)->getRole());
        self::assertSame('customer@example.test', $this->em()->find(User::class, $this->customerId)->getEmail());
        $this->client->request('POST', '/admin/users/' . $this->customerId . '/edit', ['user_account' => ['fullName' => 'Forged', 'email' => 'forged@example.test', 'role' => 'admin', 'active' => '1']]);
        self::assertResponseStatusCodeSame(422);
        self::assertSame('customer', $this->em()->find(User::class, $this->customerId)->getRole());
        $crawler = $this->client->request('GET', '/admin/users/' . $this->adminId . '/edit');
        $values = $crawler->filter('#userAccountForm')->form()->getPhpValues();
        $values['user_account']['role'] = 'staff';
        $values['user_account']['active'] = '0';
        $this->client->request('POST', '/admin/users/' . $this->adminId . '/edit', $values, [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseStatusCodeSame(422);
        self::assertSame('admin', $this->em()->find(User::class, $this->adminId)->getRole());
        self::assertTrue($this->em()->find(User::class, $this->adminId)->isActive());
    }

    public function testDisabledStaffCannotLoginOrKeepAnExistingSession(): void
    {
        $staffId = $this->makeStaff();
        $this->signIn($staffId);
        $this->client->request('GET', '/staff/products/');
        self::assertResponseIsSuccessful();
        $this->em()->find(User::class, $staffId)->setActive(false);
        $this->em()->flush();
        $this->client->request('GET', '/staff/products/');
        self::assertResponseRedirects('http://localhost/login');
        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler->filter('form')->form([
            '_username' => 'staff@example.test', '_password' => 'Long-test-password1!',
        ]), [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects('/login');
        $this->client->request('GET', '/staff/products/');
        self::assertResponseRedirects('http://localhost/login');
    }

    public function testAdminCanDisableReactivateAndDeleteStaffWhilePreservingProducts(): void
    {
        $staffId = $this->makeStaff();
        $product = (new Product())->setName('Preserved product')->setPrice('100.00')->setCreatedBy($this->em()->find(User::class, $staffId));
        $this->em()->persist($product);
        $this->em()->flush();
        $productId = $product->getId();
        $this->signIn($this->adminId);
        foreach (['0', '1'] as $active) {
            $crawler = $this->client->request('GET', '/admin/users/' . $staffId . '/edit');
            $this->client->submit($crawler->filter('#userAccountForm')->form(['user_account[active]' => $active]), [], ['HTTP_ORIGIN' => 'http://localhost']);
            self::assertResponseRedirects('/admin/users/');
            self::assertSame($active === '1', $this->em()->find(User::class, $staffId)->isActive());
        }
        $crawler = $this->client->request('GET', '/admin/users/');
        $this->client->submit($crawler->filter('form[action="/admin/users/' . $staffId . '"]')->form());
        self::assertNull($this->em()->find(User::class, $staffId));
        $this->em()->clear();
        self::assertNotNull($this->em()->find(Product::class, $productId));
        self::assertNull($this->em()->find(Product::class, $productId)->getCreatedBy());
    }

    public function testRoleChangesRevokeExistingStaffSessionsAndDashboardShowsRequiredTotals(): void
    {
        $staffId = $this->makeStaff();
        $this->signIn($staffId);
        $this->client->request('GET', '/staff/products/');
        self::assertResponseIsSuccessful();
        $this->em()->find(User::class, $staffId)->setRole('customer');
        $this->em()->flush();
        $this->client->request('GET', '/staff/products/');
        self::assertResponseRedirects('http://localhost/login');
        $this->signIn($this->adminId);
        $this->client->request('GET', '/admin/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#statTotalUsers', '4');
        self::assertSelectorTextContains('#statTotalStaff', '0');
        self::assertSelectorTextContains('#statTotalRecords', '1');
        self::assertSelectorExists('a[href="/account/"]');
    }

    public function testStaffLoginProfilePasswordChangeAndLogout(): void
    {
        $staffId = $this->makeStaff();
        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler->filter('form')->form([
            '_username' => 'staff@example.test', '_password' => 'Long-test-password1!',
        ]), [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects();
        $crawler = $this->client->request('GET', '/account/');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="/staff/products/"]');
        $this->client->submit($crawler->filter('#inlineProfileForm')->form([
            'profile[full_name]' => 'Updated Staff Name',
        ]), [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects('/account/?tab=profile');
        self::assertSame('Updated Staff Name', $this->em()->find(User::class, $staffId)->getFullName());
        $crawler = $this->client->request('GET', '/account/');
        $token = $crawler->filter('form[action="/account/password"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/account/password', [
            '_token' => $token, 'current_password' => 'Long-test-password1!',
            'new_password' => 'Updated-staff-password1!', 'confirm_password' => 'Updated-staff-password1!',
        ]);
        self::assertTrue(password_verify('Updated-staff-password1!', $this->em()->find(User::class, $staffId)->getPassword()));
        $crawler = $this->client->request('GET', '/account/');
        self::assertResponseIsSuccessful();
        $this->client->request('GET', $crawler->filter('a[href*="logout"]')->attr('href'), [], [], ['HTTP_REFERER' => 'http://localhost/account/']);
        self::assertResponseRedirects();
        $this->client->request('GET', '/staff/products/');
        self::assertResponseRedirects('http://localhost/login');
    }
}
