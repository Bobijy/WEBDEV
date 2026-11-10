<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminOrderController extends AbstractController
{
    #[Route('/admin/orders/', name: 'app_admin_order_index', methods: ['GET'])]
    #[Route('/staff/orders/', name: 'app_staff_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository, Request $request, PaginationService $paginationService): Response
    {
        $this->requireManager();

        // Filter by status if provided in query string
        $status = $request->query->get('status');
        $query = $orderRepository->createQueryBuilder('o')->addSelect('u')->join('o.user', 'u')->orderBy('o.created_at', 'DESC')->addOrderBy('o.id', 'DESC');
        if ($status) {
            $query->andWhere('o.status = :status')->setParameter('status', $status);
        }
        $pagination = $paginationService->paginate($query, $request->query->getInt('page', 1));

        return $this->render('admin/order/index.html.twig', [
            'orders' => $pagination['items'],
            'pagination' => $pagination,
            'currentStatus' => $status
        ]);
    }

    #[Route('/admin/orders/{id}/edit', name: 'app_admin_order_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[Route('/staff/orders/{id}/edit', name: 'app_staff_order_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function edit(Order $order): Response
    {
        $this->requireManager();
        return $this->render('admin/order/edit.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/admin/orders/{id}/status', name: 'app_admin_order_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[Route('/staff/orders/{id}/status', name: 'app_staff_order_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateStatus(Request $request, Order $order, OrderService $orderService): Response
    {
        $this->requireManager();

        $newStatus = $request->request->get('status');
        $validStatuses = [...OrderService::STATUSES, 'Cancelled'];

        if (in_array($newStatus, $validStatuses, true) && $this->isCsrfTokenValid('update_status' . $order->getId(), $request->request->get('_token'))) {
            try {
                $orderService->updateStatus($order, $newStatus);
                $this->addFlash('success', 'Order status updated to ' . $newStatus);
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid status update request.');
        }

        return $this->redirectToRoute(str_starts_with($request->attributes->get('_route', ''), 'app_staff_') ? 'app_staff_order_index' : 'app_admin_order_index');
    }

    #[Route('/admin/orders/{id}/receipt', name: 'app_admin_order_receipt', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[Route('/staff/orders/{id}/receipt', name: 'app_staff_order_receipt', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function receipt(Order $order): Response
    {
        $this->requireManager();

        return $this->render('order/receipt.html.twig', [
            'order' => $order,
        ]);
    }

    private function requireManager(): void
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->denyAccessUnlessGranted('ROLE_STAFF');
        }
    }
}

