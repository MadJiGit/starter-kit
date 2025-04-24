<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ErrorController extends AbstractController
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[Route('/error', name: 'error_page')]
    public function showError(Request $request): Response
    {
        $messageParam = $request->query->get('message', 'error.unexpected');
        $message = str_contains($messageParam, ' ')
            ? $messageParam
            : $this->translator->trans($messageParam, [], 'flash_messages_translate');
        $referer = $request->query->get('referer', '/');

        return $this->render('error_page.html.twig', [
            'message' => urldecode($message),
            'referer' => $referer,
        ]);
    }
}
