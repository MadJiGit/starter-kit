<?php

namespace App\Controller;

use App\Form\ContactFormType;
use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/home')]
class HomeController extends AbstractController
{
    private TranslatorInterface $translator;
    private EmailService $emailService;

    public function __construct(TranslatorInterface $translator, EmailService $emailService)
    {
        $this->translator = $translator;
        $this->emailService = $emailService;
    }

    #[Route('/privacy', name: 'privacy_policy')]
    public function privacyPolicy(): Response
    {
        return $this->render('privacy_policy.html.twig');
    }

    #[Route('/contact', name: 'mail_contact')]
    public function contact(Request $request): Response
    {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'] ?? 'invalid_email@mail.com';
            $subject_pref = $this->translator->trans('emails.contact.subject_pref');
            $subject = $subject_pref.' '.($data['name'] ?? 'Unknown');
            $context = $data['message'] ?? 'No message provided';

            $success = $this->emailService->sendEmail(
                $email,
                $subject,
                $context,
                'contact'
            );

            if ($success) {
                $this->addFlash('success', $this->translator->trans('contact.success', [], 'flash_messages_translate'));
            } else {
                $this->addFlash('danger', $this->translator->trans('contact.error', [], 'flash_messages_translate'));
            }

            return $this->redirectToRoute('mail_contact');
        }

        return $this->render('emails/contact_form.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }
}
