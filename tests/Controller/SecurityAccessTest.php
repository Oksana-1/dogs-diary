<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityAccessTest extends WebTestCase
{
    public function testLoginPageIsPubliclyAccessible(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
    }

    public function testDiaryPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        self::assertResponseRedirects('/login');
    }

    public function testApiRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/dogs');

        self::assertResponseRedirects('/login');
    }
}
