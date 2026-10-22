<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        $collections = $productRepository->findBy(['status' => 'Active'], ['id' => 'ASC'], 12);
        
        // Duplicate items if 3 or fewer to keep infinite slider smooth (Legacy logic ported)
        if (count($collections) > 0 && count($collections) <= 3) {
            $collections = array_merge($collections, $collections, $collections);
        }

        return $this->render('home/index.html.twig', [
            'collections' => $collections,
        ]);
    }
}
