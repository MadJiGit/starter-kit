<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/register')]
class RegistrationController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private TranslatorInterface $translator;
    private EmailService $emailService;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, LoggerInterface $logger, TranslatorInterface $translator, EmailService $emailService)
    {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->emailService = $emailService;
    }

    #[Route('/new', name: 'app_register')]
    public function register(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email'));
            $username = trim($request->request->get('username'));
            $plainPassword = $request->request->get('password');

            // Validate input fields
            if (empty($email) || empty($username) || empty($plainPassword)) {
                $this->addFlash('error', $this->translator->trans('register.fields_required', [], 'flash_messages_translate'));

                return $this->redirectToRoute('app_register');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', $this->translator->trans('register.invalid_email', [], 'flash_messages_translate'));

                return $this->redirectToRoute('app_register');
            }

            if (strlen($plainPassword) < 6) {
                $this->addFlash('error', $this->translator->trans('register.password_too_short', [], 'flash_messages_translate'));

                return $this->redirectToRoute('app_register');
            }

            // Check if email already exists
            $existingEmail = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingEmail) {
                $this->addFlash('error', $this->translator->trans('register.email_exists', [], 'flash_messages_translate'));

                return $this->redirectToRoute('app_register');
            }

            // **Check if username already exists**
            $existingUsername = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
            if ($existingUsername) {
                $this->addFlash('error', $this->translator->trans('register.username_exists', [], 'flash_messages_translate'));

                return $this->redirectToRoute('app_register');
            }

            // Create new user
            $user = new User();
            $user->setEmail($email);
            $user->setUsername($username);
            $user->setRoles(['ROLE_USER']); // Default role
            $user->setIsActive(false); // Must confirm email first

            // Hash password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // Generate secure confirmation token
            $token = bin2hex(random_bytes(32));
            $user->setConfirmationToken($token);
            $user->setTokenExpiresAt(new \DateTime('+24 hours')); // Token expires in 24 hours

            try {
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // Send confirmation email
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
                    $this->addFlash('success', $this->translator->trans('register.success', [], 'flash_messages_translate'));
                } else {
                    $this->addFlash('error', $this->translator->trans('security.send_email_fail', [], 'flash_messages_translate'));
                }

                return $this->redirectToRoute('app_login');

            } catch (TransportExceptionInterface $e) {
                $this->addFlash('error', $this->translator->trans('register.confirmation_error', [], 'flash_messages_translate'));

                return $this->redirectToRoute('app_register');
            }
        }

        return $this->render('registration/register.html.twig');
    }

    #[Route('/confirm/{token}', name: 'app_confirm_email')]
    public function confirmEmail(string $token): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['confirmationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', $this->translator->trans('register.invalid_token', [], 'flash_messages_translate'));
            $message = $this->translator->trans('emails.expired_token');

            return $this->render('registration/confirmation_failed.html.twig', [
                'message' => $message,
            ]);
        }

        if ($user->getTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('warning', $this->translator->trans('register.token_expired', [], 'flash_messages_translate'));
            $message = $this->translator->trans('emails.expired_link');

            return $this->render('registration/confirmation_failed.html.twig', [
                'message' => $message,
            ]);
        }

        $user->setIsActive(true); // Activate the account
        $user->setConfirmationToken(null); // Remove the token
        $user->setTokenExpiresAt(null); // Remove expiration date
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('register.email_verified', [], 'flash_messages_translate'));

        return $this->redirectToRoute('app_login');
    }

    #[Route('/resend_confirmation', name: 'app_resend_confirmation')]
    public function resendConfirmation(Request $request): Response
    {
        $email = $request->query->get('email'); // Get email from URL parameter
        $session = $request->getSession();
        $existingFlashMessages = $session->getFlashBag()->peekAll();

        if (!$email) {
            if (!isset($existingFlashMessages['error'])) {
                $this->addFlash('error', $this->translator->trans('register.invalid_email', [], 'flash_messages_translate'));
            }

            return $this->redirectToRoute('app_login');
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->logger->debug($user->isActive());

        if (!$user || $user->isActive()) {
            if (!isset($existingFlashMessages['error'])) {
                $this->addFlash('error', $this->translator->trans('register.already_confirmed_or_not_found', [], 'flash_messages_translate'));
            }

            return $this->redirectToRoute('app_login');
        }

        $token = bin2hex(random_bytes(32));
        $user->setConfirmationToken($token);
        $user->setTokenExpiresAt(new \DateTime('+24 hours'));

        $this->entityManager->flush();

        $confirmationLink = $this->generateUrl(
            'app_confirm_email',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $subject = $this->translator->trans('emails.no_reply.confirm_email');

        $success = $this->emailService->sendTemplatedEmail(
            $user->getEmail(),
            $subject,
            'emails/confirmation.html.twig',
            ['confirmationUrl' => $confirmationLink],
            'no_reply'
        );

        if ($success) {
            if (!isset($existingFlashMessages['success'])) {
                $this->addFlash('success', $this->translator->trans('register.confirmation_resent', [], 'flash_messages_translate'));
            }
        } else {
            $this->addFlash('error', $this->translator->trans('security.send_email_fail', [], 'flash_messages_translate'));
        }

        return $this->redirectToRoute('app_login');
    }
}
