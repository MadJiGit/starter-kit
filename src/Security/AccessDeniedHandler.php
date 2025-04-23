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
        //        return new RedirectResponse($request->headers->get('referer', '/'));
        $referer = $request->headers->get('referer', $this->router->generate('user_dashboard'));

        return new RedirectResponse($referer);
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($_ENV['APP_DEBUG']) {
            return;
        }

        $exception = $event->getThrowable();
        $referer = '/postures/all';

        //         dd($exception->getMessage());

        if ($exception instanceof AccessDeniedHttpException || $exception instanceof NotFoundHttpException) {
            $request = $event->getRequest();
            $referer = $request->headers->get('referer') ?? '/user/me';

            $session = $request->getSession();
            if (method_exists($session, 'getFlashBag')) {
                $session->getFlashBag()->add('warning', 'access_denied.message');
            }

            $response = new RedirectResponse($referer);
            $event->setResponse($response);
        }

        //        dd($exception->getMessage());
        //        dump($exception->getMessage());

        $errorMessage = match (true) {
            str_contains($exception->getMessage(), 'password cannot be null') => 'The password field is required.',
            str_contains($exception->getMessage(), 'Integrity constraint violation') => 'This action violates database constraints.',
            str_contains($exception->getMessage(), 'No route found') => 'The page you requested does not exist.',
            default => 'An unexpected error occurred. Please try again.',
        };

        $errorUrl = $this->router->generate('error_page', [
            'message' => urlencode($errorMessage),
            'referer' => urlencode($referer),
        ]);
        $response = new RedirectResponse($errorUrl);
        $event->setResponse($response);
    }
}
