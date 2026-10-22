<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\DBAL\LockMode;
use Symfony\Bundle\SecurityBundle\Security;

class CartService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {
    }

    public function getActiveCart(bool $create = true, bool $refresh = false): ?Cart
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user) {
            return null;
        }

        $query = $this->em->getRepository(Cart::class)->createQueryBuilder('c')
            ->addSelect('i', 'p')
            ->leftJoin('c.cartItems', 'i')
            ->leftJoin('i.product', 'p')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery();
        if ($refresh) {
            $query->setHint(Query::HINT_REFRESH, true);
        }
        $cart = $query->getOneOrNullResult();
        if (!$cart && $create) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->em->persist($cart);
            $this->em->flush();
        }

        return $cart;
    }

    public function addToCart(Product $product, int $quantity = 1): void
    {
        $this->assertQuantity($product, $quantity);
        $this->em->wrapInTransaction(function () use ($product, $quantity): void {
            $this->lockUser();
            $this->em->refresh($product, LockMode::PESSIMISTIC_WRITE);
            $cart = $this->getActiveCart(true, true);
            if (!$cart) {
                throw new \DomainException('Please sign in to manage your bag.');
            }

            $existingItem = null;
            foreach ($cart->getCartItems() as $item) {
                if ($item->getProduct()->getId() === $product->getId()) {
                    $existingItem = $item;
                    break;
                }
            }

            if ($existingItem) {
                $this->assertQuantity($product, $existingItem->getQuantity() + $quantity);
                $existingItem->setQuantity($existingItem->getQuantity() + $quantity);
            } else {
                $this->assertQuantity($product, $quantity);
                $cartItem = new CartItem();
                $cartItem->setProduct($product);
                $cartItem->setQuantity($quantity);
                $cart->addCartItem($cartItem);
                $this->em->persist($cartItem);
            }

        });
    }

    public function updateQuantity(Product $product, int $quantity): void
    {
        $this->assertQuantity($product, $quantity);
        $this->em->wrapInTransaction(function () use ($product, $quantity): void {
            $this->lockUser();
            $this->em->refresh($product, LockMode::PESSIMISTIC_WRITE);
            $this->assertQuantity($product, $quantity);
            $cart = $this->getActiveCart(false, true);
            foreach ($cart?->getCartItems() ?? [] as $item) {
                if ($item->getProduct()->getId() === $product->getId()) {
                    $item->setQuantity($quantity);
                    return;
                }
            }
            throw new \DomainException('This product is not in your bag.');
        });
    }

    private function assertQuantity(Product $product, int $quantity): void
    {
        if ($quantity < 1 || $quantity > 1000) {
            throw new \DomainException('Quantity must be between 1 and 1000.');
        }
        if ($product->getStatus() !== 'Active' || $quantity > ($product->getStock() ?? 0)) {
            throw new \DomainException('This quantity is unavailable. Please check the remaining stock.');
        }
    }

    private function lockUser(): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \DomainException('Please sign in to manage your bag.');
        }
        $this->em->refresh($user, LockMode::PESSIMISTIC_WRITE);
    }

    public function removeFromCart(int $productId): void
    {
        $this->em->wrapInTransaction(function () use ($productId): void {
            $this->lockUser();
            $cart = $this->getActiveCart(false, true);
            foreach ($cart?->getCartItems() ?? [] as $item) {
                if ($item->getProduct()->getId() === $productId) {
                    $cart->removeCartItem($item);
                    $this->em->remove($item);
                    break;
                }
            }
        });
    }

    public function clearCart(): void
    {
        $this->em->wrapInTransaction(function (): void {
            $this->lockUser();
            $cart = $this->getActiveCart(false, true);
            foreach ($cart?->getCartItems() ?? [] as $item) {
                $cart->removeCartItem($item);
                $this->em->remove($item);
            }
        });
    }

    public function confirmOrder(Order $order): void
    {
        $this->em->wrapInTransaction(function () use ($order): void {
            $this->lockUser();
            $cart = $this->getActiveCart(false, true);
            foreach ($cart?->getCartItems() ?? [] as $item) {
                foreach ($order->getOrderItems() as $purchased) {
                    if ($purchased->getProduct()->getId() === $item->getProduct()->getId()) {
                        $remaining = $item->getQuantity() - $purchased->getQuantity();
                        if ($remaining > 0) {
                            $item->setQuantity($remaining);
                        } else {
                            $cart->removeCartItem($item);
                            $this->em->remove($item);
                        }
                        break;
                    }
                }
            }
        });
    }

    public function getCartTotals(): array
    {
        $cart = $this->getActiveCart(false);
        if (!$cart) {
            return ['subtotal' => 0.0, 'total' => 0.0, 'shipping' => 0.0, 'item_count' => 0, 'items' => []];
        }

        $subtotal = 0;
        $itemsData = [];
        foreach ($cart->getCartItems() as $item) {
            $product = $item->getProduct();
            $itemTotal = Money::toCents($product->getPrice()) * $item->getQuantity();
            $subtotal += $itemTotal;
            $itemsData[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'quantity' => $item->getQuantity(),
                'total' => $itemTotal / 100,
                'image' => $product->getImage()
            ];
        }

        return [
            'subtotal' => $subtotal / 100,
            'shipping' => 0.0, // Shipping is added by checkout.
            'total' => $subtotal / 100,
            'items' => $itemsData,
            'item_count' => count($itemsData)
        ];
    }
}
