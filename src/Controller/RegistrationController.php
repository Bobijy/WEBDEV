<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    #[IsCsrfTokenValid('register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, Security $security, ValidatorInterface $validator): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name', ''));
            $email = mb_strtolower(trim($request->request->get('email', '')));
            $plainPassword = $request->request->get('password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            // Basic validation logic porting from legacy api/auth.php
            if ($plainPassword !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match.');
            } elseif (strlen($plainPassword) < 12 || strlen($plainPassword) > 72 || trim($plainPassword) === '') {
                $this->addFlash('error', 'Use a password between 12 and 72 bytes.');
            } else {
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existingUser) {
                    $this->addFlash('error', 'An account with this email already exists.');
                } else {
                    $user = new User();
                    $user->setFullName($name);
                    $user->setEmail($email);
                    $errors = $validator->validate($user);
                    if (count($errors) > 0) {
                        foreach ($errors as $error) {
                            $this->addFlash('error', $error->getMessage());
                        }
                        return $this->redirectToRoute('app_register');
                    }
                    
                    // The auto hasher configured in security.yaml will use modern hashes but remains compatible with PASSWORD_DEFAULT
                    $user->setPassword(
                        $userPasswordHasher->hashPassword(
                            $user,
                            $plainPassword
                        )
                    );

                    $entityManager->persist($user);
                    try {
                        $entityManager->flush();
                    } catch (UniqueConstraintViolationException) {
                        $this->addFlash('error', 'An account with this email already exists.');
                        return $this->redirectToRoute('app_register');
                    }

                    // Automatically login after registration
                    return $security->login($user, 'form_login', 'main') ?? $this->redirectToRoute('app_home');
                }
            }
        }

        return $this->render('registration/register.html.twig');
    }
}
