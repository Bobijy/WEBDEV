<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cart')]
class CartController extends AbstractController
{
    public function __construct(
        private CartService $cartService
    ) {
    }

    #[Route('/add/{id}', name: 'api_cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['success' => false, 'message' => 'Please log in to add items to your bag.'], 401);
        }

        if (!$this->isCsrfTokenValid('cart', $request->request->get('_token'))) {
            return $this->json(['success' => false, 'message' => 'Please reload the page and try again.'], 403);
        }
        $quantity = filter_var($request->request->get('quantity', '1'), FILTER_VALIDATE_INT);
        try {
            if ($quantity === false) {
                throw new \DomainException('Please provide a whole-number quantity.');
            }
            $this->cartService->addToCart($product, $quantity);
        } catch (\DomainException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Added to cart successfully',
            'cart' => $this->cartService->getCartTotals()
        ]);
    }

    #[Route('/update/{id}', name: 'api_cart_update', methods: ['POST'])]
    public function update(Product $product, Request $request): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$this->isCsrfTokenValid('cart', $request->request->get('_token'))) {
            return $this->json(['success' => false, 'message' => 'Please reload the page and try again.'], 403);
        }
        $quantity = filter_var($request->request->get('quantity', '1'), FILTER_VALIDATE_INT);
        try {
            if ($quantity === false) {
                throw new \DomainException('Please provide a whole-number quantity.');
            }
            $this->cartService->updateQuantity($product, $quantity);
        } catch (\DomainException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Cart updated',
            'cart' => $this->cartService->getCartTotals()
        ]);
    }

    #[Route('/remove/{id}', name: 'api_cart_remove', methods: ['POST'])]
    public function remove(Product $product, Request $request): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$this->isCsrfTokenValid('cart', $request->request->get('_token'))) {
            return $this->json(['success' => false, 'message' => 'Please reload the page and try again.'], 403);
        }
        $this->cartService->removeFromCart($product->getId());

        return new JsonResponse([
            'success' => true,
            'message' => 'Removed from cart',
            'cart' => $this->cartService->getCartTotals()
        ]);
    }

    #[Route('/view', name: 'api_cart_view', methods: ['GET'])]
    public function view(): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['success' => false, 'cart' => ['items' => [], 'total' => 0.0]]);
        }

        return new JsonResponse([
            'success' => true,
            'cart' => $this->cartService->getCartTotals()
        ]);
    }
}
