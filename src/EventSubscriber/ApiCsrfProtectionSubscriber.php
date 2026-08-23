<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class ApiCsrfProtectionSubscriber implements EventSubscriberInterface
{
    private const TOKEN_ID = 'api';
    private const TOKEN_HEADER = 'X-CSRF-TOKEN';
    private const PROTECTED_METHODS = [
        Request::METHOD_POST,
        Request::METHOD_PUT,
        Request::METHOD_PATCH,
        Request::METHOD_DELETE,
    ];

    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Run after the firewall has populated the security token.
        return [KernelEvents::REQUEST => ['onKernelRequest', 0]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isProtectedApiRequest($request) || !$this->isAuthenticated()) {
            return;
        }

        $token = new CsrfToken(self::TOKEN_ID, $request->headers->get(self::TOKEN_HEADER, ''));
        if ($this->csrfTokenManager->isTokenValid($token)) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => [
                'code' => 'invalid_csrf_token',
                'message' => 'The CSRF token is missing or invalid.',
            ],
        ], Response::HTTP_FORBIDDEN));
    }

    private function isProtectedApiRequest(Request $request): bool
    {
        $path = $request->getPathInfo();

        return ('/api' === $path || str_starts_with($path, '/api/'))
            && in_array($request->getMethod(), self::PROTECTED_METHODS, true);
    }

    private function isAuthenticated(): bool
    {
        return $this->tokenStorage->getToken()?->getUser() instanceof UserInterface;
    }
}
