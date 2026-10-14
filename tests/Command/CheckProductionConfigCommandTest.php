<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\CheckProductionConfigCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CheckProductionConfigCommandTest extends TestCase
{
    public function testHostedPreviewRequiresTlsAndDisabledMailWithoutLeakingSecrets(): void
    {
        $make = static fn (string $query, string $mailer) => new CommandTester(new CheckProductionConfigCommand(
            environment: 'prod', debug: false, appSecret: str_repeat('b', 64),
            databaseUrl: 'postgresql://preview:private-password@preview.example.com/preview'.$query,
            mailerDsn: $mailer, mailerFromAddress: 'no-reply@dogs-diary.test',
            mailerFromName: 'Preview', appBaseUrl: 'https://preview.example.com',
        ));
        $safe = $make('?sslmode=verify-full', 'null://null');
        self::assertSame(Command::SUCCESS, $safe->execute(['--preview' => true, '--require-tls' => true]));
        $unsafe = $make('?sslmode=prefer', 'smtp://user:mail-secret@mail.example.com');
        self::assertSame(Command::FAILURE, $unsafe->execute(['--preview' => true, '--require-tls' => true]));
        self::assertStringContainsString('must require PostgreSQL TLS', $unsafe->getDisplay());
        self::assertStringContainsString('Preview MAILER_DSN must be null://null', $unsafe->getDisplay());
        self::assertStringNotContainsString('private-password', $unsafe->getDisplay());
        self::assertStringNotContainsString('mail-secret', $unsafe->getDisplay());
    }

    public function testItAcceptsSafeProductionConfiguration(): void
    {
        $tester = new CommandTester(new CheckProductionConfigCommand(
            environment: 'prod',
            debug: false,
            appSecret: str_repeat('a', 64),
            databaseUrl: 'postgresql://app_user:private-db-password@postgres.example.com:5432/dogs_diary',
            mailerDsn: 'smtp://mailer_user:mailer_password@smtp.example.com:587',
            mailerFromAddress: 'no-reply@example.com',
            mailerFromName: 'Dogs Diary',
            appBaseUrl: 'https://dogs-diary.example.com',
        ));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Production configuration is safe to use.', $tester->getDisplay());
        self::assertStringNotContainsString('private-db-password', $tester->getDisplay());
        self::assertStringNotContainsString(str_repeat('a', 64), $tester->getDisplay());
    }

    public function testItRejectsUnsafeDefaultsWithoutDisplayingTheirValues(): void
    {
        $unsafeSecret = '73a15be21da244448ba8820e4aa531f2';
        $unsafeDatabaseUrl = 'postgresql://app:!ChangeMe!@127.0.0.1:5432/app';
        $tester = new CommandTester(new CheckProductionConfigCommand(
            environment: 'dev',
            debug: true,
            appSecret: $unsafeSecret,
            databaseUrl: $unsafeDatabaseUrl,
            mailerDsn: 'null://null',
            mailerFromAddress: 'not-an-email',
            mailerFromName: '',
            appBaseUrl: 'http://localhost:8080/reset?token=secret',
        ));

        self::assertSame(Command::FAILURE, $tester->execute([]));

        $display = $tester->getDisplay();
        self::assertStringContainsString('APP_ENV must be "prod".', $display);
        self::assertStringContainsString('APP_DEBUG must be disabled.', $display);
        self::assertStringContainsString('APP_SECRET must not reuse', $display);
        self::assertStringContainsString('DATABASE_URL must not point to localhost.', $display);
        self::assertStringContainsString('DATABASE_URL must not use placeholder credentials.', $display);
        self::assertStringContainsString('MAILER_DSN must configure a real production transport.', $display);
        self::assertStringContainsString('APP_BASE_URL must be an HTTPS origin', $display);
        self::assertStringNotContainsString($unsafeSecret, $display);
        self::assertStringNotContainsString($unsafeDatabaseUrl, $display);
    }
}
