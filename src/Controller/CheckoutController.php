<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\User;
use App\Service\CartService;
use App\Service\OrderService;
use App\Service\AddressService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/checkout', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function index(Request $request, AddressService $addressService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($request->isMethod('POST') && !$this->isCsrfTokenValid('checkout', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid checkout token.');
        }
        /** @var User $user */
        $user = $this->getUser();
        $receiptOrder = null;
        if ($orderId = $request->query->get('order_id')) {
            $receiptOrder = $this->entityManager->getRepository(Order::class)->find($orderId);
            if (!$receiptOrder || $receiptOrder->getUser() !== $user) {
                throw $this->createAccessDeniedException();
            }
        }
        $session = $request->getSession();
        if (!$session->has('checkout_token')) {
            $session->set('checkout_token', bin2hex(random_bytes(16)));
        }
        $cartTotals = $this->cartService->getCartTotals();
        if (empty($cartTotals['items']) && !$receiptOrder) {
            $this->addFlash('error', 'Your bag is empty.');
            return $this->redirectToRoute('app_shop');
        }

        if ($request->isMethod('POST')) {
            try {
                $token = $request->request->get('checkout_token', '');
                if (!hash_equals($session->get('checkout_token'), $token)) {
                    throw new \DomainException('Please reload checkout before placing your order.');
                }
                $newAddress = null;
                $selected = $request->request->get('selected_address_id', '');
                if ($selected === 'new') {
                    $newAddress = $addressService->update((new Address())->setUser($user), $request->request->all());
                    $shippingAddress = $newAddress->getFullAddressText();
                } elseif ($selected === 'default') {
                    $shippingAddress = $user->getAddress() ?? '';
                } else {
                    $address = $this->entityManager->getRepository(Address::class)->findOneBy(['id' => $selected, 'user' => $user]);
                    if (!$address) {
                        throw new \DomainException('Please select one of your saved delivery addresses.');
                    }
                    $shippingAddress = $address->getFullAddressText();
                }
                $order = $this->orderService->processCheckout([
                    'shipping_address' => $shippingAddress,
                    'payment_method' => $request->request->get('payment_method', ''),
                ], $token, $newAddress);

                return $this->redirectToRoute('app_checkout', ['order_id' => $order->getId()]);
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Throwable $e) {
                $this->logger->error('Checkout failed.', ['exception' => $e]);
                $this->addFlash('error', 'We could not place your order. Please try again.');
            }
            return $this->redirectToRoute('app_checkout');
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cartTotals,
            'receiptOrder' => $receiptOrder,
            'checkout_token' => $session->get('checkout_token'),
            'shipping_fee' => OrderService::SHIPPING_FEE,
        ]);
    }

    #[Route('/checkout/confirm', name: 'app_checkout_confirm', methods: ['POST'])]
    public function confirm(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if (!$this->isCsrfTokenValid('confirm_order', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid confirmation token.');
        }
        $order = $this->entityManager->getRepository(Order::class)->find($request->request->get('order_id', ''));
        $token = $request->getSession()->get('checkout_token', '');
        if (!$order || $order->getUser() !== $this->getUser() || !$token || $order->getCheckoutToken() !== $token) {
            throw $this->createAccessDeniedException();
        }
        $this->cartService->confirmOrder($order);
        $request->getSession()->remove('checkout_token');
        $this->addFlash('success', 'Thank you! Your order has been placed successfully.');

        return $this->redirectToRoute('app_account', ['tab' => 'orders']);
    }
}
