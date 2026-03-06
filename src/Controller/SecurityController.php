<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // si l'utilisateur est déjà connecté, on le redirige vers la page d'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_shop_index');
        }

        // recupère l'erreur de connexion s'il y en a une
        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        MailerService $mailerService,
    ): Response {
        // si l'utilisateur est déjà connecté, on le redirige
        if ($this->getUser()) {
            return $this->redirectToRoute('app_shop_index');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            // Vérification CSRF
            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_csrf_token', ''))) {
                $errors[] = 'Token CSRF invalide.';
            } else {
                $email = trim((string) $request->request->get('email', ''));
                $plainPassword = (string) $request->request->get('password', '');
                $confirmPassword = (string) $request->request->get('confirm_password', '');

                // Vérification que les mots de passe correspondent
                if ($plainPassword !== $confirmPassword) {
                    $errors[] = 'Les mots de passe ne correspondent pas.';
                }

                if (strlen($plainPassword) < 6) {
                    $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
                }

                if (empty($errors)) {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

                    // Validation des contraintes de l'entité (email unique, format, etc.)
                    $violations = $validator->validate($user);
                    if (count($violations) > 0) {
                        foreach ($violations as $violation) {
                            $errors[] = $violation->getMessage();
                        }
                    }

                    // Vérifier que l'email n'existe pas déjà
                    if (empty($errors)) {
                        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                        if ($existing) {
                            $errors[] = 'Un compte avec cet email existe déjà.';
                        }
                    }

                    if (empty($errors)) {
                        $em->persist($user);
                        $em->flush();

                        // Envoi de l'email de bienvenue
                        $mailerService->sendWelcomeEmail($email);

                        $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');

                        return $this->redirectToRoute('app_login');
                    }
                }
            }
        }

        return $this->render('security/register.html.twig', [
            'errors' => $errors,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony intercepte cette route avant d'arriver ici
        // la methode peut rester vide
    }
}
