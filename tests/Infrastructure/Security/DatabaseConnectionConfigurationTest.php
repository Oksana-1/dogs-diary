<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DatabaseConnectionConfigurationTest extends KernelTestCase
{
    public function testEncodedCredentialsAndTlsPathArePreservedWithoutConnecting(): void
    {
        $previousServer = $_SERVER['DATABASE_URL'] ?? null;
        $previousEnvironment = $_ENV['DATABASE_URL'] ?? null;
        $previousProcess = getenv('DATABASE_URL');
        $url = 'postgresql://preview:p%25%40ss@db.invalid/neondb?sslmode=verify-full&sslrootcert=%2Fetc%2Fssl%2Fcerts%2Fca-certificates.crt&serverVersion=18';
        $_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $url;
        putenv('DATABASE_URL='.$url);

        try {
            self::bootKernel();
            $parameters = self::getContainer()->get('doctrine')->getConnection()->getParams();
            self::assertSame('p%@ss', $parameters['password']);
            self::assertSame('verify-full', $parameters['sslmode']);
            self::assertSame('/etc/ssl/certs/ca-certificates.crt', $parameters['sslrootcert']);
        } finally {
            self::ensureKernelShutdown();
            if (null === $previousServer) {
                unset($_SERVER['DATABASE_URL']);
            } else {
                $_SERVER['DATABASE_URL'] = $previousServer;
            }
            if (null === $previousEnvironment) {
                unset($_ENV['DATABASE_URL']);
            } else {
                $_ENV['DATABASE_URL'] = $previousEnvironment;
            }
            putenv(false === $previousProcess ? 'DATABASE_URL' : 'DATABASE_URL='.$previousProcess);
        }
    }
}
