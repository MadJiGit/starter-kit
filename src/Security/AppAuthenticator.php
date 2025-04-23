<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private RouterInterface $router;
    private UserRepository $userRepository;
    private LoggerInterface $logger;
    private TranslatorInterface $translator;

    public function __construct(RouterInterface $router, UserRepository $userRepository, LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->router = $router;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->translator = $translator;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_email', '');
        $password = $request->request->get('_password', '');
        $csrfToken = $request->request->get('_csrf_token', '');

        $this->logger->info("Authentication attempt for email: " . $email);

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $this->logger->error("Authentication failed: User not found.");
            throw new CustomUserMessageAuthenticationException('auth.user_not_found');
        }

        if (!$user->isActive()) {
            $this->logger->error("Authentication failed: User is not verified.");
            throw new CustomUserMessageAuthenticationException('auth.email_not_verified');
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
                new PasswordUpgradeBadge($password),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var UserInterface $user */
        $user = $token->getUser();
        $locale = $request->getLocale();
        $roles = $user->getRoles();

        if (in_array('ROLE_SUPER_ADMIN', $roles) || in_array('ROLE_ADMIN', $roles)) {
            return new RedirectResponse($this->router->generate('admin_dashboard', ['_locale' => $locale]));
        }

        if (in_array('ROLE_USER', $roles, true)) {
            return new RedirectResponse($this->router->generate('user_dashboard', ['_locale' => $locale]));
        }

        return new RedirectResponse($this->router->generate('app_login', ['_locale' => $locale]));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $session = $request->getSession();
        $locale = $request->getLocale();

        if (!$session->isStarted()) {
            $session->start();
        }

        $session->set('last_username', $request->request->get('_email', ''));
        $message = $exception->getMessage();
        $this->logger->error("Authentication failed: " . $message);

        if (!$session->getFlashBag()->has('error')) {
            $translatedMessage = $this->translator->trans($message, [], 'flash_messages_translate');
            $session->getFlashBag()->add('error', $translatedMessage);
        }

        return new RedirectResponse($this->router->generate('app_login', ['_locale' => $locale]));
    }

    protected function getLoginUrl(Request $request): string
    {
        $locale = $request->getLocale();
        return $this->router->generate('app_login', ['_locale' => $locale]);
    }
}