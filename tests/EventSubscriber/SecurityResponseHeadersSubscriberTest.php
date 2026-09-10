<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\SecurityResponseHeadersSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SecurityResponseHeadersSubscriberTest extends TestCase
{
    public function testAddsBaselineSecurityHeadersAndHstsToSecureProductionResponses(): void
    {
        $response = $this->dispatch('prod', Request::create('https://dogs-diary.example/dogs'));

        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame('DENY', $response->headers->get('X-Frame-Options'));
        self::assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        self::assertSame('camera=(), geolocation=(), microphone=()', $response->headers->get('Permissions-Policy'));
        self::assertSame(
            'max-age=31536000',
            $response->headers->get('Strict-Transport-Security'),
        );
    }

    public function testDoesNotAddHstsOutsideSecureProductionRequests(): void
    {
        self::assertFalse(
            $this->dispatch('prod', Request::create('http://dogs-diary.example'))->headers
                ->has('Strict-Transport-Security'),
        );
        self::assertFalse(
            $this->dispatch('test', Request::create('https://dogs-diary.example'))->headers
                ->has('Strict-Transport-Security'),
        );
    }

    private function dispatch(string $environment, Request $request): Response
    {
        $response = new Response();
        $event = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        (new SecurityResponseHeadersSubscriber($environment))->onKernelResponse($event);

        return $response;
    }
}
