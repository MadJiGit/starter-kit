<?php

namespace App\Controller;

use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user')]
#[IsGranted('ROLE_USER')]
class TemporaryController  extends AbstractController
{
    public function __construct(private EmailService $emailService, private TranslatorInterface $translator,)
    {
    }

    #[Route('/path_1', name: 'path_1', methods: ['GET'])]
    public function method_1(): Response
    {
        return $this->render('/temporary.html.twig', [
            'controller_name' => 'Temporary page',
        ]);
    }

    #[Route('/path_2', name: 'path_2', methods: ['GET'])]
    public function method_2(): Response
    {
        return $this->render('/temporary.html.twig', [
            'controller_name' => 'Temporary page',
        ]);
    }

    #[Route('/send_email', name: 'send_email', methods: ['GET'])]
    public function method_3(): Response
    {
        $confirmationLink =
            'app_confirm_email';

        $subject = "test test test";

        $success = $this->emailService->sendEmail(
            'custom@email.com',
            $subject,
            'emails/confirmation.html.twig',
            ['confirmationUrl' => $confirmationLink],
            'no_reply'
        );

        if ($success) {
            $this->addFlash('success', $this->translator->trans('register.confirmation_resent', [], 'flash_messages_translate'));
        } else {
            $this->addFlash('error', $this->translator->trans('security.send_email_fail', [], 'flash_messages_translate'));
        }

        return $this->render('/temporary.html.twig', [
            'controller_name' => 'Temporary page',
        ]);
    }
}