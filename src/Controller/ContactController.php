<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ContactController extends AbstractController
{
    #[Route('/contact/submit', name: 'app_contact_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = $request->request->all();
        }
        if (!$this->isCsrfTokenValid('contact', is_string($data['_token'] ?? null) ? $data['_token'] : null)) {
            return $this->json(['success' => false, 'message' => 'Please reload the page and try again.'], 403);
        }
        foreach (['name', 'email', 'message'] as $field) {
            if (isset($data[$field]) && !is_string($data[$field])) {
                return $this->json(['success' => false, 'message' => 'Invalid form values.'], 400);
            }
        }
        if (!$data) {
            $data = $request->request->all();
        }

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $messageText = trim($data['message'] ?? '');

        // Basic validation
        $errors = [];
        if (mb_strlen($name) > 255 || mb_strlen($email) > 255 || mb_strlen($messageText) > 5000) {
            $errors[] = 'Name and email must be at most 255 characters and messages at most 5000.';
        }
        if (empty($name)) {
            $errors[] = 'Name is required.';
        }
        
        $emailConstraint = new Assert\Email();
        $emailErrors = $validator->validate($email, $emailConstraint);
        if (empty($email) || count($emailErrors) > 0) {
            $errors[] = 'A valid email is required.';
        }
        
        if (empty($messageText)) {
            $errors[] = 'Message cannot be empty.';
        }

        if (count($errors) > 0) {
            return new JsonResponse(['success' => false, 'message' => implode(' ', $errors)], 400);
        }

        try {
            $contactMessage = new ContactMessage();
            $contactMessage->setName($name);
            $contactMessage->setEmail($email);
            $contactMessage->setMessage($messageText);
            
            if ($this->getUser()) {
                $contactMessage->setUser($this->getUser());
            }

            $entityManager->persist($contactMessage);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Your message has been sent successfully!']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'An error occurred while saving your message.'], 500);
        }
    }
}
