<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ContactFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/home')]
class HomeController extends AbstractController
{
    private ContainerInterface $containerFull;
    private TranslatorInterface $translator;

    public function __construct(ContainerInterface $container, TranslatorInterface $translator)
    {
        $this->containerFull = $container;
        $this->translator = $translator;
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

            try {
                $mailer = $this->containerFull->get('mailer.contact');

                $email = (new Email())
                    ->from('contact@body-language.org') // нужно за SMTP
                    ->replyTo($data['email'])           // потребителят, който пише
                    ->to('contact@body-language.org')
                    ->subject('Съобщение от ' . $data['name'])
                    ->text($data['message']);

                $mailer->send($email);

                $this->addFlash('success', $this->translator->trans('contact.success', [], 'flash_messages_translate'));
            } catch (\Throwable $e) {
                $this->addFlash('danger', $this->translator->trans('contact.error', ['%error%' => $e->getMessage()], 'flash_messages_translate'));
            }

            return $this->redirectToRoute('mail_contact');
        }

        return $this->render('emails/contact_form.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }
}