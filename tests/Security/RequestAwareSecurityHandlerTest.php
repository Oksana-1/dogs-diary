<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\RequestAwareSecurityHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class RequestAwareSecurityHandlerTest extends TestCase
{
    private RequestAwareSecurityHandler $handler;

    protected function setUp(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->with('app_login')->willReturn('/login');
        $this->handler = new RequestAwareSecurityHandler($urlGenerator);
    }

    public function testWebAuthenticationStartsWithALoginRedirect(): void
    {
        $response = $this->handler->start(Request::create('/dog/42'));

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/login', $response->headers->get('location'));
    }

    public function testApiAuthenticationStartsWithAJsonUnauthorizedResponse(): void
    {
        $response = $this->handler->start(Request::create('/api/dogs'));

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('content-type'));
        self::assertSame(
            ['error' => ['code' => 'authentication_required', 'message' => 'Authentication is required.']],
            json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR),
        );
    }

    public function testAuthenticatedApiAccessDenialReturnsJsonForbidden(): void
    {
        $response = $this->handler->handle(Request::create('/api/dogs'), new AccessDeniedException());

        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode());
        self::assertSame(
            ['error' => ['code' => 'access_denied', 'message' => 'Access denied.']],
            json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR),
        );
    }

    public function testWebAccessDenialUsesSymfonyDefaultHandling(): void
    {
        self::assertNull($this->handler->handle(Request::create('/dog/42'), new AccessDeniedException()));
    }
}
