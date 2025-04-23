<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TokenStorageInterface $tokenStorage;
    private TranslatorInterface $translator;
    private EmailService $emailService;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager, TranslatorInterface $translator, EmailService $emailService)
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->emailService = $emailService;
    }

    #[Route('/me', name: 'user_dashboard', methods: ['GET'])]
    public function userDashboard(): Response
    {
        $user = $this->getUser();

        return $this->render('/dashboard.html.twig', [
            'controller_name' => 'User Dashboard',
            'user' => $user,
        ]);
    }

    #[Route('/profile', name: 'user_profile')]
    public function userProfile(): Response
    {
        return $this->render('user/user_profile.html.twig', [
            'controller_name' => 'User Profile',
        ]);
    }

    #[Route('/profile/edit', name: 'user_edit_profile')]
    public function editProfile(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $originalUsername = $user->getUsername();
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldPassword = $form->get('oldPassword')->getData();
            $newUsername = $form->get('username')->getData();
            if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
                $this->addFlash('danger', $this->translator->trans('user.incorrect_password', [], 'flash_messages_translate'));
                return $this->redirectToRoute('user_edit_profile');
            }

            if ($newUsername !== $originalUsername) {
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $newUsername]);

                if ($existingUser) {
                    $this->addFlash('error', $this->translator->trans('user.username_exists', [], 'flash_messages_translate'));
                    return $this->redirectToRoute('user_edit_profile');
                }

                $user->setUsername($newUsername);
            }

            $newPassword = $form->get('newPassword')->getData();
            if ($newPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
            }

            $token = bin2hex(random_bytes(32));
            $user->setConfirmationToken($token);
            $user->setTokenExpiresAt((new \DateTime())->modify('+30 minutes')); // Валиден 30 мин.

            $user->setIsActive(false);
            $this->entityManager->flush();

            $confirmationLink = $this->generateUrl('app_confirm_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            $subject = $this->translator->trans('emails.no_reply.confirm_email');

            $success = $this->emailService->sendTemplatedEmail(
                $user->getEmail(),
                $subject,
                'emails/confirmation.html.twig',
                ['confirmationUrl' => $confirmationLink],
                'no_reply'
            );

            if ($success) {
                $this->addFlash('success', $this->translator->trans('user.change_successful', [], 'flash_messages_translate'));
                $this->tokenStorage->setToken(null);
                $request->getSession()->invalidate();
                $this->addFlash('success', $this->translator->trans('user.confirmation_required', [], 'flash_messages_translate'));
            } else {
                $this->addFlash('error', $this->translator->trans('security.send_email_fail', [], 'flash_messages_translate'));
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/profile_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}