<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final readonly class RequestAwareSecurityHandler implements AuthenticationEntryPointInterface, AccessDeniedHandlerInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        if ($this->isApiRequest($request)) {
            return $this->apiError(
                Response::HTTP_UNAUTHORIZED,
                'authentication_required',
                'Authentication is required.',
            );
        }

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        if (!$this->isApiRequest($request)) {
            return null;
        }

        return $this->apiError(
            Response::HTTP_FORBIDDEN,
            'access_denied',
            'Access denied.',
        );
    }

    private function isApiRequest(Request $request): bool
    {
        $path = $request->getPathInfo();

        return '/api' === $path || str_starts_with($path, '/api/');
    }

    private function apiError(int $status, string $code, string $message): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
