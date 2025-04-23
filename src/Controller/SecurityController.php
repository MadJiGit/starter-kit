<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ForgottenPasswordType;
use App\Form\ResetPasswordType;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    private TranslatorInterface $translator;
    private EmailService $emailService;

    public function __construct(private EntityManagerInterface $entityManager, TranslatorInterface $translator, EmailService $emailService)
    {
        $this->translator = $translator;
        $this->emailService = $emailService;
    }

    #[\Symfony\Component\Routing\Annotation\Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {

        if ($this->getUser()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $session = $request->getSession();
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername() ?: $session->get('last_username', '');
        $resendEmail = false;

        if (!empty($lastUsername)) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $lastUsername]);

            if ($user && !$user->isActive() && !isset($existingFlashMessages['error'])) {
                $resendEmail = true;
                $this->addFlash('warning', $this->translator->trans('security.email_not_verified', [], 'flash_messages_translate'));
            }
        }

        if ($error) {
            $this->addFlash('error', $error->getMessageKey());
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'resend_email' => $resendEmail,
            'error' => $error,
        ]);
    }

    #[Route('/forgotten_pass', name: 'forgotten_pass')]
    public function forgottenPassword(Request $request): Response
    {
        $form = $this->createForm(ForgottenPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'] ?? null;

            $session = $request->getSession();
            $existingFlashMessages = $session->getFlashBag()->peekAll();
            if (!$email) {
                if (!isset($existingFlashMessages['error'])) {
                    $this->addFlash('error', $this->translator->trans('security.invalid_email', [], 'flash_messages_translate'));
                }
                return $this->redirectToRoute('app_login');
            }

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                if (!isset($existingFlashMessages['error'])) {
                    $this->addFlash('error', $this->translator->trans('security.email_not_found', [], 'flash_messages_translate'));
                }
                return $this->redirectToRoute('app_register');
            } else if ($user && !$user->isActive()) {
                if (!isset($existingFlashMessages['error'])) {
                    $this->addFlash('error', $this->translator->trans('security.email_not_verified', [], 'flash_messages_translate'));
                }
                return $this->redirectToRoute('app_login');
            }

            $token = bin2hex(random_bytes(32));
            $user->setConfirmationToken($token);
            $user->setTokenExpiresAt(new \DateTime('+10 minutes'));

            $this->entityManager->flush();

            $confirmationLink = $this->generateUrl(
                'app_new_password',
                ['token' => $token],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $subject = $this->translator->trans('emails.no_reply.restore_pass');

            $success = $this->emailService->sendEmail(
                $user->getEmail(),
                $subject,
                'emails/confirmation_reset_password.html.twig',
                ['confirmationUrl' => $confirmationLink],
                'no_reply'
            );

            if ($success) {
                if (!isset($existingFlashMessages['success'])) {
                    $this->addFlash('success', $this->translator->trans('security.reset_email_sent', [], 'flash_messages_translate'));
                }
            } else {
                $this->addFlash('error', $this->translator->trans('security.send_email_fail', [], 'flash_messages_translate'));
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgotten_pass.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new_password', name: 'app_new_password')]
    public function newPassword(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $token = $request->query->get('token');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['confirmationToken' => $token]);

        if (!$user || $user->getTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', $this->translator->trans('security.invalid_token', [], 'flash_messages_translate'));
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $newPassword = $data['plainPassword'];

            $newConfirmationToken = bin2hex(random_bytes(32));

            $hashedTempPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setTempPassword($hashedTempPassword);
            $user->setConfirmationToken($newConfirmationToken);
            $user->setTokenExpiresAt(new \DateTime('+10 minutes'));
            $this->entityManager->flush();

            $confirmationLink = $this->generateUrl(
                'app_confirm_new_password',
                ['token' => $newConfirmationToken],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $subject = $this->translator->trans('emails.no_reply.confirm_pass');

            $success = $this->emailService->sendEmail(
                $user->getEmail(),
                $subject,
                'emails/confirm_new_password.html.twig',
                ['confirmationUrl' => $confirmationLink],
                'no_reply'
            );

            if ($success) {
                $this->addFlash('success', $this->translator->trans('security.reset_confirmation_sent', [], 'flash_messages_translate'));
            } else {
                $this->addFlash('error', $this->translator->trans('security.send_email_fail', [], 'flash_messages_translate'));
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/confirm_new_password', name: 'app_confirm_new_password')]
    public function confirmNewPassword(Request $request): Response
    {
        $token = $request->query->get('token');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['confirmationToken' => $token]);

        if (!$user || $user->getTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', $this->translator->trans('security.invalid_token', [], 'flash_messages_translate'));
            return $this->redirectToRoute('app_login');
        }

        if (!$user->getTempPassword()) {
            $this->addFlash('error', $this->translator->trans('security.temp_password_missing', [], 'flash_messages_translate'));
            return $this->redirectToRoute('app_login');
        }

        $user->setPassword($user->getTempPassword());
        $user->setTempPassword(null);
        $user->setConfirmationToken(null);
        $user->setTokenExpiresAt(null);

        $this->entityManager->flush();

        $confirmationLink = $this->generateUrl(
            'app_login',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $subject = $this->translator->trans('emails.no_reply.pass_changed');

        $success = $this->emailService->sendEmail(
            $user->getEmail(),
            $subject,
            'emails/password_changed.html.twig',
            ['user' => $user,
            'confirmationUrl' => $confirmationLink],
            'no_reply'
        );

        if ($success) {
            $this->addFlash('success', $this->translator->trans('security.password_changed', [], 'flash_messages_translate'));
        } else {
            $this->addFlash('error', $this->translator->trans('security.send_email_fail', [], 'flash_messages_translate'));
        }

        return $this->redirectToRoute('app_login');
    }

    /**
     * @throws \Exception
     */
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new \Exception('This should never be reached!');
    }
}
