<?php

namespace App\Controller\Admin;

use App\Entity\ContactMessage;
use App\Entity\ContactReply;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use App\Service\PaginationService;

#[Route('/admin/messages')]
class AdminContactController extends AbstractController
{
    #[Route('/', name: 'app_admin_contact_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginationService $paginationService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('c')
            ->from(ContactMessage::class, 'c')
            ->orderBy('c.createdAt', 'DESC');

        $search = $request->query->get('q');
        if ($search) {
            $queryBuilder->where('c.name LIKE :search OR c.email LIKE :search')
                         ->setParameter('search', '%' . $search . '%');
        }

        $pagination = $paginationService->paginate($queryBuilder, $request->query->getInt('page', 1));
        $messages = $pagination['items'];
        
        $unreadCount = $entityManager->createQueryBuilder()
            ->select('count(c.id)')
            ->from(ContactMessage::class, 'c')
            ->where('c.status = :status')
            ->setParameter('status', 'Unread')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/contact/index.html.twig', [
            'messages' => $messages,
            'pagination' => $pagination,
            'search' => $search,
            'unreadCount' => $unreadCount
        ]);
    }

    #[Route('/{id}/read', name: 'app_admin_contact_read', methods: ['POST'])]
    public function toggleRead(Request $request, ContactMessage $message, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('read'.$message->getId(), $request->request->get('_token'))) {
            $newStatus = $message->getStatus() === 'Unread' ? 'Read' : 'Unread';
            $message->setStatus($newStatus);
            $entityManager->flush();
            $this->addFlash('success', 'Message marked as ' . $newStatus . '.');
        }

        return $this->redirectToRoute('app_admin_contact_index');
    }

    
    #[Route('/{id}/reply', name: 'app_admin_contact_reply', methods: ['POST'])]
    #[IsCsrfTokenValid('reply')]
    public function reply(Request $request, ContactMessage $message, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $replyText = trim($request->request->get('reply_message', ''));
        if (mb_strlen($replyText) > 5000) {
            $this->addFlash('error', 'Replies must be at most 5000 characters.');
            return $this->redirectToRoute('app_admin_contact_show', ['id' => $message->getId()]);
        }
        if (!empty($replyText)) {
            $reply = new ContactReply();
            $reply->setContactMessage($message);
            $reply->setMessage($replyText);
            $reply->setIsFromAdmin(true);

            $message->setStatus('Replied');

            $entityManager->persist($reply);
            $entityManager->flush();

            $this->addFlash('success', 'Reply sent successfully.');
        }

        return $this->redirectToRoute('app_admin_contact_show', ['id' => $message->getId()]);
    }

    #[Route('/{id}', name: 'app_admin_contact_show', methods: ['GET'])]
    public function show(ContactMessage $message, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($message->getStatus() === 'Unread') {
            $message->setStatus('Read');
            $entityManager->flush();
        }

        return $this->render('admin/contact/show.html.twig', [
            'message' => $message,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_contact_delete', methods: ['POST'])]

    public function delete(Request $request, ContactMessage $message, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$message->getId(), $request->request->get('_token'))) {
            $entityManager->remove($message);
            $entityManager->flush();
            $this->addFlash('success', 'Message deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_contact_index');
    }
}
