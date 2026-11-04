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

#[Route('/admin/orders')]
class AdminOrderController extends AbstractController
{
    #[Route('/', name: 'app_admin_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository, Request $request, PaginationService $paginationService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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

    #[Route('/{id}/edit', name: 'app_admin_order_edit', methods: ['GET'])]
    public function edit(Order $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/order/edit.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/status', name: 'app_admin_order_status', methods: ['POST'])]
    public function updateStatus(Request $request, Order $order, OrderService $orderService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $newStatus = $request->request->get('status');
        $validStatuses = ['Pending', 'Approved', 'Processing', 'Shipped', 'Completed', 'Cancelled'];

        if (in_array($newStatus, $validStatuses) && $this->isCsrfTokenValid('update_status' . $order->getId(), $request->request->get('_token'))) {
            try {
                $orderService->updateStatus($order, $newStatus);
                $this->addFlash('success', 'Order status updated to ' . $newStatus);
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid status update request.');
        }

        return $this->redirectToRoute('app_admin_order_index');
    }

    #[Route('/{id}/receipt', name: 'app_admin_order_receipt', methods: ['GET'])]
    public function receipt(Order $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('order/receipt.html.twig', [
            'order' => $order,
        ]);
    }
}

