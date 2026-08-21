<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;

final class SecurityAccessTest extends WebTestCase
{
    #[DataProvider('publicPageProvider')]
    public function testAuthenticationPagesArePubliclyAccessible(string $path): void
    {
        $client = static::createClient();

        $client->request('GET', $path);

        self::assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function publicPageProvider(): iterable
    {
        yield 'login' => ['/login'];
        yield 'registration' => ['/sign-up'];
        yield 'password-reset request' => ['/reset-password'];
        yield 'password-reset confirmation' => ['/reset-password/check-email'];
        yield 'new password' => ['/reset-password/new'];
    }

    #[DataProvider('protectedWebPageProvider')]
    public function testWebPagesRedirectAnonymousVisitorsToLogin(string $path): void
    {
        $client = static::createClient();

        $client->request('GET', $path);

        self::assertResponseRedirects('/login');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function protectedWebPageProvider(): iterable
    {
        yield 'diary homepage' => ['/'];
        yield 'dog detail' => ['/dog/42'];
    }

    #[DataProvider('protectedApiProvider')]
    public function testApiReturnsJsonUnauthorizedForAnonymousRequests(string $method, string $path): void
    {
        $client = static::createClient();

        $client->request($method, $path);

        self::assertResponseStatusCodeSame(401);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertSame([
            'error' => [
                'code' => 'authentication_required',
                'message' => 'Authentication is required.',
            ],
        ], json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function protectedApiProvider(): iterable
    {
        yield 'dog list' => ['GET', '/api/dogs'];
        yield 'dog creation' => ['POST', '/api/dogs'];
        yield 'dog retrieval' => ['GET', '/api/dogs/42'];
        yield 'dog update' => ['PUT', '/api/dogs/42'];
        yield 'dog deletion' => ['DELETE', '/api/dogs/42'];
        yield 'treatment list' => ['GET', '/api/dogs/42/treatments'];
        yield 'dog media upload' => ['POST', '/api/dogs/42/media'];
        yield 'treatment media deletion' => ['DELETE', '/api/dogs/42/treatments/7/media/9'];
    }

    public function testMappedAssetsRemainPubliclyAccessible(): void
    {
        $client = static::createClient();
        $assetMapper = self::getContainer()->get(AssetMapperInterface::class);

        $client->request('GET', $assetMapper->getPublicPath('app.js'));

        self::assertResponseIsSuccessful();
    }
}
