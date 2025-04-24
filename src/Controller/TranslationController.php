<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TranslationController extends AbstractController
{
    #[Route('/js/js_translations.js', name: 'js_translations')]
    public function jsTranslations(): Response
    {
        return $this->render('js/js_translations.js.twig', [], new Response('', 200, [
            'Content-Type' => 'application/javascript',
        ]));
    }
}
