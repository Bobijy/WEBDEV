<?php

declare(strict_types=1);

use App\Entity\Address;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Kernel;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\Tools\SchemaTool;

$_SERVER['APP_SECRET'] = $_ENV['APP_SECRET'] = 'local-qa-preview-only-20261011';
require dirname(__DIR__) . '/vendor/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

$kernel = new Kernel('qa', false);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$connection = $em->getConnection();
$expected = str_replace('\\', '/', dirname(__DIR__) . '/var/qa/preview.sqlite');
if (!$connection->getDatabasePlatform() instanceof SQLitePlatform || str_replace('\\', '/', $connection->getParams()['path'] ?? '') !== $expected) {
    throw new LogicException('Preview seeding is restricted to var/qa/preview.sqlite.');
}
$tool = new SchemaTool($em);
$metadata = $em->getMetadataFactory()->getAllMetadata();
$tool->dropSchema($metadata);
$tool->createSchema($metadata);
$users = [];
foreach (['customer', 'admin'] as $role) {
    $user = (new User())->setFullName(ucfirst($role) . ' Demo')->setEmail($role . '@example.test')
        ->setPassword(password_hash('Demo-project-password1!', PASSWORD_BCRYPT))->setRole($role);
    $em->persist($user);
    $users[] = $user;
}
$products = [];
foreach (range(1, 30) as $number) {
    $product = (new Product())->setName(['Velvet Rose', 'Amber Silk', 'Midnight Oud'][($number - 1) % 3] . ' ' . $number)
        ->setDescription('An elegant fragrance from Maison Ungod.')->setPrice(number_format(145.35 + $number, 2, '.', ''))
        ->setStock(20)->setCategory($number % 2 ? 'Floral' : 'Woody')->setImage('assets/images/' . ($number % 2 ? 'pour-homme.png' : 'pour-femme.png'));
    $em->persist($product);
    $products[] = $product;
}
foreach ($users as $user) {
    $address = (new Address())->setUser($user)->setFullName($user->getFullName())->setFirstName('Demo')->setLastName('Customer')
        ->setPhone('09123456789')->setStreetAddress('10 Test Street, Manila, Philippines')->setAddressLine1('10 Test Street')
        ->setCity('Manila')->setRegion('Metro Manila')->setCountry('Philippines')->setPostalCode('1000')->setDefault(true);
    $em->persist($address);
    $cart = (new Cart())->setUser($user);
    $cart->addCartItem((new CartItem())->setProduct($products[0])->setQuantity(1));
    $em->persist($cart);
    foreach (['Pending', 'Completed'] as $status) {
        $order = (new Order())->setUser($user)->setOrderNumber('DEMO-' . strtoupper($status) . '-' . $user->getRole())
            ->setTotalAmount('246.35')->setShippingFee('100.00')->setShippingAddress($address->getFullAddressText())
            ->setPaymentMethod('Cash On Delivery')->setStatus($status);
        $order->addOrderItem((new OrderItem())->setProduct($products[0])->setQuantity(1)->setPrice('146.35'));
        $em->persist($order);
    }
}
$em->flush();
echo "Synthetic QA preview seeded.\n";
