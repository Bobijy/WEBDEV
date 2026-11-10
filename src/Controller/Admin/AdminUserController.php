<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Dto\UserAccountData;
use App\Form\UserAccountType;
use App\Service\UserManagementService;
use App\Repository\UserRepository;
use App\Service\PaginationService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\Form\FormError;
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
        $role = $request->query->get('role', '');
        if (in_array($role, ['admin', 'staff', 'customer'], true)) {
            $query->andWhere('u.role = :role')->setParameter('role', $role);
        }
        $status = $request->query->get('status', '');
        if (in_array($status, ['active', 'disabled'], true)) {
            $query->andWhere('u.active = :active')->setParameter('active', $status === 'active');
        }
        $pagination = $paginationService->paginate($query, $request->query->getInt('page', 1));
        return $this->render('admin/user/index.html.twig', [
            'pagination' => $pagination,
            'users' => $pagination['items'],
        ]);
    }

    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserManagementService $users): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->accountForm($request, new User(), new UserAccountData(), $users);
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserManagementService $users): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->accountForm($request, $user, UserAccountData::fromUser($user), $users);
    }

    private function accountForm(Request $request, User $user, UserAccountData $data, UserManagementService $users): Response
    {
        $new = $user->getId() === null;
        $form = $this->createForm(UserAccountType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $users->save($this->getUser(), $user, $data);
                $this->addFlash('success', $new ? 'Account created successfully.' : 'Account updated successfully.');
                return $this->redirectToRoute('app_admin_user_index');
            } catch (\DomainException $e) {
                $form->addError(new FormError($e->getMessage()));
            } catch (UniqueConstraintViolationException) {
                $form->get('email')->addError(new FormError('An account with this email already exists.'));
            }
        }
        return $this->render('admin/user/form.html.twig', [
            'form' => $form, 'account' => $user, 'isNew' => $new,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}', name: 'app_admin_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, User $user, UserManagementService $users): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Please reload the page and try again.');
        }
        try {
            $users->delete($this->getUser(), $user);
            $this->addFlash('success', 'User deleted successfully.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', 'This account has linked records. Disable it instead to preserve its history.');
        }

        return $this->redirectToRoute('app_admin_user_index');
    }
}
