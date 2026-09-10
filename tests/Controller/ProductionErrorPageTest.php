<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bridge\Twig\ErrorRenderer\TwigErrorRenderer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final class ProductionErrorPageTest extends KernelTestCase
{
    public function testProductionErrorPagesAreFriendlyAndDoNotExposeExceptionDetails(): void
    {
        self::bootKernel();
        $renderer = new TwigErrorRenderer(self::getContainer()->get(Environment::class), debug: false);

        $cases = [
            [new AccessDeniedHttpException('private authorization detail'), 403, 'Access denied'],
            [new NotFoundHttpException('private routing detail'), 404, 'Page not found'],
            [new \RuntimeException('private stack detail'), 500, 'Something went wrong'],
        ];

        foreach ($cases as [$exception, $status, $heading]) {
            $rendered = $renderer->render($exception);

            self::assertSame($status, $rendered->getStatusCode());
            self::assertStringContainsString($heading, $rendered->getAsString());
            self::assertStringContainsString('Back to Dogs Diary', $rendered->getAsString());
            self::assertStringNotContainsString($exception->getMessage(), $rendered->getAsString());
        }
    }
}
