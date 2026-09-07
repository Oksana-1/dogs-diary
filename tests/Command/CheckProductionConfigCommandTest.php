<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\CheckProductionConfigCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CheckProductionConfigCommandTest extends TestCase
{
    public function testItAcceptsSafeProductionConfiguration(): void
    {
        $tester = new CommandTester(new CheckProductionConfigCommand(
            environment: 'prod',
            debug: false,
            appSecret: str_repeat('a', 64),
            databaseUrl: 'postgresql://railway_user:railway_password@postgres.railway.internal:5432/railway',
            mailerDsn: 'smtp://mailer_user:mailer_password@smtp.example.com:587',
            mailerFromAddress: 'no-reply@example.com',
            mailerFromName: 'Dogs Diary',
            appBaseUrl: 'https://dogs-diary.example.com',
        ));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Production configuration is safe to use.', $tester->getDisplay());
        self::assertStringNotContainsString('railway_password', $tester->getDisplay());
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
