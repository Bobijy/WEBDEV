<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminDashboardController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        UserRepository $userRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Gathering stats for the dashboard (Porting logic from admin/index.php)
        $totalProducts = $productRepository->count([]);
        
        $totalStock = (int) $productRepository->createQueryBuilder('p')->select('COALESCE(SUM(p.stock), 0)')->getQuery()->getSingleScalarResult();
        
        $statusCounts = [
            'Pending' => 0,
            'Approved' => 0,
            'Processing' => 0,
            'Shipped' => 0,
            'Completed' => 0,
        ];

        $counts = $orderRepository->createQueryBuilder('o')->select('o.status AS status, COUNT(o.id) AS total')->groupBy('o.status')->getQuery()->getArrayResult();
        foreach ($counts as $row) {
            if (isset($statusCounts[$row['status']])) {
                $statusCounts[$row['status']] = (int) $row['total'];
            }
        }

        $totalCustomers = $userRepository->count(['role' => 'customer']);

        $bestSellingProducts = $productRepository->findBestSellingProducts(5);

        return $this->render('admin/dashboard/index.html.twig', [
            'totalProducts' => $totalProducts,
            'totalStock' => $totalStock,
            'statusCounts' => $statusCounts,
            'totalCustomers' => $totalCustomers,
            'recentOrders' => $orderRepository->findBy([], ['created_at' => 'DESC'], 5),
            'bestSellingProducts' => $bestSellingProducts,
        ]);
    }
}
