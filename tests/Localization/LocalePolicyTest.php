<?php

declare(strict_types=1);

namespace App\Tests\Localization;

use App\Localization\LocalePolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class LocalePolicyTest extends TestCase
{
    #[DataProvider('preferences')]
    public function testResolvesOnlySupportedLocales(?string $cookie, string $accept, string $expected): void
    {
        $request = Request::create('/', cookies: null === $cookie ? [] : [LocalePolicy::COOKIE => $cookie], server: ['HTTP_ACCEPT_LANGUAGE' => $accept]);
        self::assertSame($expected, (new LocalePolicy())->resolve($request));
    }

    public static function preferences(): iterable
    {
        yield 'cookie wins' => ['en', 'uk-UA,en;q=0.8', 'en'];
        yield 'uk cookie' => ['uk', 'en', 'uk'];
        yield 'invalid cookie defaults' => ['fr', 'uk', 'en'];
        yield 'empty cookie defaults' => ['', 'uk', 'en'];
        yield 'regional Ukrainian' => [null, 'fr,uk-UA;q=0.9,en;q=0.5', 'uk'];
        yield 'regional English' => [null, 'en-GB,uk;q=0.9', 'en'];
        yield 'quality order' => [null, 'en;q=0.5,uk;q=0.9', 'uk'];
        yield 'refused language' => [null, 'uk;q=0,fr', 'en'];
        yield 'unsupported' => [null, 'fr,de;q=0.9', 'en'];
        yield 'missing' => [null, '', 'en'];
    }
}
