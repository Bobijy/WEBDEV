<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\ProfileType;
use App\Dto\ProfileData;
use App\Entity\ContactMessage;
use App\Entity\ContactReply;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use App\Service\OrderService;
use App\Service\AddressService;
use App\Entity\Address;
use Doctrine\DBAL\LockMode;

#[Route('/account')]
class AccountController extends AbstractController
{
    #[Route('/', name: 'app_account', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em, OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Invalid form values must not change the authenticated session identity.
        $profile = ProfileData::fromUser($user);
        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        $activeTab = $request->query->get('tab', 'profile');

        if ($form->isSubmitted() && $form->isValid()) {
            $profile->applyTo($user);
            $em->flush();
            $this->addFlash('success', 'Profile updated successfully.');
            return $this->redirectToRoute('app_account', ['tab' => 'profile']);
        }

        $orders = $orderRepository->findBy(
            ['user' => $user],
            ['created_at' => 'DESC']
        );

        $messages = $em->getRepository(ContactMessage::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('account/index.html.twig', [
            'form' => $form->createView(),
            'orders' => $orders,
            'messages' => $messages,
            'active_tab' => $activeTab,
            'user' => $user
        ], new Response(status: $form->isSubmitted() && !$form->isValid() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/messages/{id}', name: 'app_account_message_show', methods: ['GET', 'POST'])]
    #[IsCsrfTokenValid('reply', methods: ['POST'])]
    public function messageShow(ContactMessage $message, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($message->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $replyText = trim($request->request->get('reply_message', ''));
            if (mb_strlen($replyText) > 5000) {
                $this->addFlash('error', 'Replies must be at most 5000 characters.');
                return $this->redirectToRoute('app_account_message_show', ['id' => $message->getId()]);
            }
            if (!empty($replyText)) {
                $reply = new ContactReply();
                $reply->setContactMessage($message);
                $reply->setMessage($replyText);
                $reply->setIsFromAdmin(false);

                $message->setStatus('Unread');

                $em->persist($reply);
                $em->flush();

                return $this->redirectToRoute('app_account_message_show', ['id' => $message->getId()]);
            }
        }

        return $this->render('account/message_show.html.twig', [
            'message' => $message,
        ]);
    }

    #[Route('/order/{id}/cancel', name: 'app_account_order_cancel', methods: ['POST'])]
    public function cancelOrder(Order $order, Request $request, OrderService $orderService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('cancel_order' . $order->getId(), $request->request->get('_token'))) {
            try {
                $orderService->cancel($order);
                $this->addFlash('success', 'Order cancelled successfully.');
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_account', ['tab' => 'orders']);
    }

    #[Route('/address/{id}/default', name: 'app_account_address_default', methods: ['POST'])]
    #[IsCsrfTokenValid('address_default')]
    public function setDefaultAddress(\App\Entity\Address $address, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($address->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $userAddresses = $em->getRepository(\App\Entity\Address::class)->findBy(['user' => $this->getUser()]);
        foreach ($userAddresses as $a) {
            $a->setDefault(false);
        }

        $address->setDefault(true);
        $em->flush();

        $this->addFlash('success', 'Default address updated.');
        return $this->redirectToRoute('app_account', ['tab' => 'addresses']);
    }

    #[Route('/password', name: 'app_account_password', methods: ['POST'])]
    #[IsCsrfTokenValid('password')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $current = $request->request->get('current_password', '');
        $new = $request->request->get('new_password', '');
        $confirm = $request->request->get('confirm_password', '');
        if (strlen($new) < 12 || strlen($new) > 72 || trim($new) === '') {
            $this->addFlash('error', 'Use a password between 12 and 72 bytes.');
            return $this->redirectToRoute('app_account');
        }

        if (!$passwordHasher->isPasswordValid($user, $current)) {
            $this->addFlash('error', 'Current password is incorrect.');
            return $this->redirectToRoute('app_account');
        }

        if ($new !== $confirm) {
            $this->addFlash('error', 'New passwords do not match.');
            return $this->redirectToRoute('app_account');
        }

        $user->setPassword($passwordHasher->hashPassword($user, $new));
        $em->flush();

        $this->addFlash('success', 'Password changed successfully.');
        return $this->redirectToRoute('app_account');
    }

    #[Route('/address/update', name: 'app_account_address_update', methods: ['POST'])]
    public function updateAddress(Request $request, EntityManagerInterface $em, AddressService $addresses): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if (!$this->isCsrfTokenValid('update_address', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $user = $this->getUser();
        $id = $request->request->get('address_id', '');
        $address = $id === '' ? (new Address())->setUser($user) : $em->getRepository(Address::class)->findOneBy(['id' => $id, 'user' => $user]);
        if (!$address) {
            throw $this->createAccessDeniedException();
        }
        try {
            $addresses->update($address, $request->request->all());
            $em->wrapInTransaction(function () use ($em, $user, $address, $id): void {
                $em->refresh($user, LockMode::PESSIMISTIC_WRITE);
                if ($id === '') {
                    $address->setDefault($user->getAddresses()->isEmpty());
                    $user->addAddress($address);
                    $em->persist($address);
                }
            });
            $this->addFlash('success', 'Address saved successfully.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_account', ['tab' => 'addresses']);
    }
}
