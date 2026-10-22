<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Service\PaginationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShopController extends AbstractController
{
    #[Route('/shop', name: 'app_shop')]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository, Request $request, PaginationService $paginationService): Response
    {
        $query = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->where('p.status = :status')
            ->setParameter('status', 'Active');
        $search = mb_substr(trim($request->query->get('q', '')), 0, 100);
        $category = mb_substr($request->query->get('category', ''), 0, 100);
        if ($search !== '') {
            $query->andWhere('p.name LIKE :search OR p.brand LIKE :search OR c.name LIKE :search')->setParameter('search', '%' . $search . '%');
        }
        if ($category !== '') {
            $query->andWhere('c.name = :category OR c.id = :catId')
                ->setParameter('category', $category)
                ->setParameter('catId', is_numeric($category) ? (int)$category : 0);
        }
        $sorts = ['default' => ['p.id', 'ASC'], 'price-asc' => ['p.price', 'ASC'], 'price-desc' => ['p.price', 'DESC'], 'name-asc' => ['p.name', 'ASC'], 'name-desc' => ['p.name', 'DESC']];
        [$field, $direction] = $sorts[$request->query->get('sort', 'default')] ?? $sorts['default'];
        $query->orderBy($field, $direction)->addOrderBy('p.id', 'ASC');
        $pagination = $paginationService->paginate($query, $request->query->getInt('page', 1));
        
        return $this->render('shop/index.html.twig', [
            'products' => $pagination['items'],
            'pagination' => $pagination,
            'search' => $search,
            'category' => $category,
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
        ]);
    }
    #[Route('/api/products/search', name: 'api_product_search', methods: ['GET'])]
    public function search(Request $request, ProductRepository $products): JsonResponse
    {
        $search = mb_substr(trim($request->query->get('q', '')), 0, 100);
        if ($search === '') {
            return $this->json(['products' => []]);
        }
        $matches = $products->createQueryBuilder('p')->where('p.status = :status')
            ->andWhere('p.name LIKE :search OR p.brand LIKE :search')
            ->setParameter('status', 'Active')->setParameter('search', '%' . $search . '%')
            ->orderBy('p.name', 'ASC')->setMaxResults(10)->getQuery()->getResult();

        return $this->json(['products' => array_map(fn ($product) => [
            'name' => $product->getName(), 'price' => '₱' . number_format((float) $product->getPrice(), 2),
            'image' => '/' . ltrim($product->getImage() ?? 'assets/logo/logo.png', '/'),
            'link' => $this->generateUrl('app_shop', ['q' => $product->getName()]),
        ], $matches)]);
    }
}
