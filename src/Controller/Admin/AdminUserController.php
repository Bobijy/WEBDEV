<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
class AdminUserController extends AbstractController
{
    #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request, PaginationService $paginationService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $query = $userRepository->createQueryBuilder('u')->orderBy('u.id', 'DESC');
        if ($search = mb_substr(trim($request->query->get('q', '')), 0, 100)) {
            $query->andWhere('u.full_name LIKE :search OR u.email LIKE :search')->setParameter('search', '%' . $search . '%');
        }
        $pagination = $paginationService->paginate($query, $request->query->getInt('page', 1));
        return $this->render('admin/user/index.html.twig', [
            'pagination' => $pagination,
            'users' => $pagination['items'],
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Prevent deleting oneself
        if ($this->getUser() === $user) {
            $this->addFlash('error', 'You cannot delete yourself.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $deleted = $entityManager->wrapInTransaction(function () use ($entityManager, $user): bool {
                $entityManager->refresh($user, LockMode::PESSIMISTIC_WRITE);
                if ($entityManager->getRepository(\App\Entity\Order::class)->count(['user' => $user]) > 0) {
                    return false;
                }
                $entityManager->remove($user);
                return true;
            });
            $this->addFlash($deleted ? 'success' : 'error', $deleted ? 'User deleted successfully.' : 'Customers with order history cannot be deleted.');
        }

        return $this->redirectToRoute('app_admin_user_index');
    }
}
