<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class AdminProductController extends AbstractController
{
    #[Route('/admin/products/', name: 'app_admin_product_index', methods: ['GET'])]
    #[Route('/staff/products/', name: 'app_staff_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository, Request $request, PaginationService $paginationService): Response
    {
        $this->requireManager();
        $query = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.createdBy', 'creator')->addSelect('creator')
            ->orderBy('p.id', 'DESC');
        if ($search = mb_substr(trim($request->query->get('q', '')), 0, 100)) {
            $query->andWhere('p.name LIKE :search OR c.name LIKE :search')->setParameter('search', '%' . $search . '%');
        }
        $status = $request->query->get('status', '');
        if (in_array($status, ['Active', 'Inactive'], true)) {
            $query->andWhere('p.status = :status')->setParameter('status', $status);
        }
        $pagination = $paginationService->paginate($query, $request->query->getInt('page', 1));
        return $this->render('admin/product/index.html.twig', [
            'pagination' => $pagination,
            'products' => $pagination['items'],
        ]);
    }

    #[Route('/admin/products/new', name: 'app_admin_product_new', methods: ['GET', 'POST'])]
    #[Route('/staff/products/new', name: 'app_staff_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->requireManager();
        $product = new Product();
        $product->setCreatedBy($this->getUser());
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.bin2hex(random_bytes(12)).'.'.$imageFile->guessExtension();

                try {
                    // Moving to the public/uploads directory
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads',
                        $newFilename
                    );
                    $product->setImage('uploads/'.$newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image.');
                    return $this->render('admin/product/new.html.twig', ['product' => $product, 'form' => $form]);
                }
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product created successfully.');
            return $this->redirectToRoute($this->productRoute($request, 'index'));
        }

        return $this->render('admin/product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/admin/products/{id}/edit', name: 'app_admin_product_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[Route('/staff/products/{id}/edit', name: 'app_staff_product_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('PRODUCT_EDIT', $product);
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.bin2hex(random_bytes(12)).'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads',
                        $newFilename
                    );
                    $product->setImage('uploads/'.$newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image.');
                    return $this->render('admin/product/edit.html.twig', ['product' => $product, 'form' => $form]);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Product updated successfully.');
            return $this->redirectToRoute($this->productRoute($request, 'index'));
        }

        return $this->render('admin/product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/admin/products/{id}', name: 'app_admin_product_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[Route('/staff/products/{id}', name: 'app_staff_product_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('PRODUCT_DELETE', $product);
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $archived = $entityManager->wrapInTransaction(function () use ($entityManager, $product): bool {
                $entityManager->refresh($product, LockMode::PESSIMISTIC_WRITE);
                if ($entityManager->getRepository(\App\Entity\OrderItem::class)->count(['product' => $product]) > 0) {
                    $product->setStatus('Inactive');
                    return true;
                }
                $entityManager->remove($product);
                return false;
            });
            $this->addFlash('success', $archived ? 'Product archived to preserve its order history.' : 'Product deleted successfully.');
        }

        return $this->redirectToRoute($this->productRoute($request, 'index'));
    }

    private function requireManager(): void
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->denyAccessUnlessGranted('ROLE_STAFF');
        }
    }

    private function productRoute(Request $request, string $action): string
    {
        return (str_starts_with($request->attributes->get('_route', ''), 'app_staff_') ? 'app_staff_product_' : 'app_admin_product_') . $action;
    }
}
