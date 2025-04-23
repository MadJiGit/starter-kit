<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function handle(Request $request, AccessDeniedException $exception): RedirectResponse
    {
        // Връща потребителя на предишната страница, ако има такава
//        return new RedirectResponse($request->headers->get('referer', '/'));
        $referer = $request->headers->get('referer', $this->router->generate('user_dashboard'));
        return new RedirectResponse($referer);
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($_ENV['APP_DEBUG']) {
            // Остави Symfony да си показва debug page
            return;
        }

        $exception = $event->getThrowable();
        $referer = '/postures/all';

         dd($exception->getMessage());

        // Проверяваме дали е AccessDeniedException (403 Forbidden)
        if ($exception instanceof AccessDeniedHttpException || $exception instanceof NotFoundHttpException) {
            $request = $event->getRequest();
            $referer = $request->headers->get('referer') ?? '/user/me';

            $request->getSession()->getFlashBag()->add('warning', 'access_denied.message');

            // Редиректваме обратно към предишната страница (или към началната)
            $response = new RedirectResponse($referer);
            $event->setResponse($response);
        }

        dd($exception->getMessage());
//        dump($exception->getMessage());

        // **Персонализиране на съобщенията според типа на грешката**
        $errorMessage = match (true) {
            str_contains($exception->getMessage(), 'password cannot be null') => 'The password field is required.',
            str_contains($exception->getMessage(), 'Integrity constraint violation') => 'This action violates database constraints.',
            str_contains($exception->getMessage(), 'No route found') => 'The page you requested does not exist.',
            default => 'An unexpected error occurred. Please try again.',
        };

        // Ако е друга грешка (като password cannot be null) -> пращаме към страницата за грешки
//        $referer = $request->headers->get('referer', '/');
        $errorUrl = $this->router->generate('error_page', [
            'message' => urlencode($errorMessage), // Кодиране на съобщението за URL
            'referer' => urlencode($referer),
        ]);
        $response = new RedirectResponse($errorUrl);
        $event->setResponse($response);
    }
}