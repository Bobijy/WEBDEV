<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class OrderService
{
    public const SHIPPING_FEE = '100.00';
    public const STATUSES = ['Pending', 'Approved', 'Processing', 'Shipped', 'Completed'];

    public function __construct(
        private EntityManagerInterface $em,
        private CartService $cartService,
        private Security $security,
    ) {
    }

    public function processCheckout(array $checkoutData, string $checkoutToken, ?Address $newAddress = null): Order
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \DomainException('Please sign in to checkout.');
        }
        if (!preg_match('/^[a-f0-9]{32}$/D', $checkoutToken)) {
            throw new \DomainException('Please reload checkout before placing your order.');
        }
        if (!in_array($checkoutData['payment_method'] ?? '', ['Cash On Delivery', 'Credit Card'], true)) {
            throw new \DomainException('Please select a supported payment method.');
        }
        $shippingAddress = trim($checkoutData['shipping_address'] ?? '');
        if ($shippingAddress === '' || mb_strlen($shippingAddress) > 1500) {
            throw new \DomainException('Please provide a valid delivery address.');
        }

        return $this->em->wrapInTransaction(function () use ($user, $checkoutData, $checkoutToken, $shippingAddress, $newAddress): Order {
            // Cart writes take the same lock, serializing checkout for this customer.
            $this->em->refresh($user, LockMode::PESSIMISTIC_WRITE);
            $existing = $this->em->getRepository(Order::class)->findOneBy([
                'user' => $user,
                'checkoutToken' => $checkoutToken,
            ]);
            if ($existing) {
                return $existing;
            }

            $cart = $this->cartService->getActiveCart(false, true);
            if (!$cart || $cart->getCartItems()->isEmpty()) {
                throw new \DomainException('Your bag is empty.');
            }
            $items = $cart->getCartItems()->toArray();
            usort($items, fn ($a, $b) => $a->getProduct()->getId() <=> $b->getProduct()->getId());
            $order = (new Order())
                ->setUser($user)
                ->setCheckoutToken($checkoutToken)
                ->setOrderNumber('ORD-' . strtoupper(bin2hex(random_bytes(8))))
                ->setShippingAddress($shippingAddress)
                ->setPaymentMethod($checkoutData['payment_method'])
                ->setShippingFee(self::SHIPPING_FEE)
                ->setStatus('Pending');
            $total = Money::toCents(self::SHIPPING_FEE);

            foreach ($items as $cartItem) {
                $product = $cartItem->getProduct();
                $this->em->refresh($product, LockMode::PESSIMISTIC_WRITE);
                $quantity = $cartItem->getQuantity();
                if ($quantity < 1 || $quantity > 1000 || $product->getStatus() !== 'Active' || ($product->getStock() ?? 0) < $quantity) {
                    throw new \DomainException('An item in your bag is unavailable. Please review your bag.');
                }
                $total += Money::toCents($product->getPrice()) * $quantity;
                $product->setStock($product->getStock() - $quantity);
                $orderItem = (new OrderItem())->setProduct($product)->setQuantity($quantity)->setPrice($product->getPrice());
                $order->addOrderItem($orderItem);
            }

            $order->setTotalAmount(Money::fromCents($total));
            if ($newAddress) {
                $newAddress->setDefault($user->getAddresses()->isEmpty());
                $user->addAddress($newAddress);
                $this->em->persist($newAddress);
            }
            $this->em->persist($order);

            // Preserve the receipt confirmation flow; confirmation clears the bag.
            return $order;
        });
    }

    public function cancel(Order $order, bool $byAdministrator = false): void
    {
        $this->em->wrapInTransaction(function () use ($order, $byAdministrator): void {
            $this->em->refresh($order, LockMode::PESSIMISTIC_WRITE);
            if ($order->getStatus() === 'Cancelled') {
                return;
            }
            $allowed = $byAdministrator ? ['Pending', 'Approved', 'Processing'] : ['Pending'];
            if (!in_array($order->getStatus(), $allowed, true)) {
                throw new \DomainException($byAdministrator ? 'Only unshipped orders can be cancelled.' : 'Only pending orders can be cancelled.');
            }
            $items = $order->getOrderItems()->toArray();
            usort($items, fn ($a, $b) => $a->getProduct()->getId() <=> $b->getProduct()->getId());
            foreach ($items as $item) {
                $product = $item->getProduct();
                $this->em->refresh($product, LockMode::PESSIMISTIC_WRITE);
                $product->setStock(($product->getStock() ?? 0) + $item->getQuantity());
            }
            $order->setStatus('Cancelled');
        });
    }

    public function updateStatus(Order $order, string $newStatus): void
    {
        if ($newStatus === 'Cancelled') {
            $this->cancel($order, true);
            return;
        }
        $this->em->wrapInTransaction(function () use ($order, $newStatus): void {
            $this->em->refresh($order, LockMode::PESSIMISTIC_WRITE);
            $current = array_search($order->getStatus(), self::STATUSES, true);
            $next = array_search($newStatus, self::STATUSES, true);
            if ($current === false || $next === false || $next < $current) {
                throw new \DomainException('This order status transition is not allowed.');
            }
            $order->setStatus($newStatus);
        });
    }
}
