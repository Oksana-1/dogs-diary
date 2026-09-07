<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:production:check',
    description: 'Validate production environment variables without displaying their values.',
)]
final class CheckProductionConfigCommand extends Command
{
    public function __construct(
        private readonly string $environment,
        private readonly bool $debug,
        private readonly string $appSecret,
        private readonly string $databaseUrl,
        private readonly string $mailerDsn,
        private readonly string $mailerFromAddress,
        private readonly string $mailerFromName,
        private readonly string $appBaseUrl,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $errors = $this->validationErrors();

        if ([] !== $errors) {
            $io->error('Production configuration is not safe.');
            $io->listing($errors);

            return Command::FAILURE;
        }

        $io->success('Production configuration is safe to use.');
        $io->writeln('Checked APP_ENV, APP_DEBUG, APP_SECRET, DATABASE_URL, mailer settings, and APP_BASE_URL.');

        return Command::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function validationErrors(): array
    {
        $errors = [];

        if ('prod' !== $this->environment) {
            $errors[] = 'APP_ENV must be "prod".';
        }

        if ($this->debug) {
            $errors[] = 'APP_DEBUG must be disabled.';
        }

        $secret = trim($this->appSecret);
        if (32 > strlen($secret)) {
            $errors[] = 'APP_SECRET must contain at least 32 characters.';
        }

        if (in_array($secret, ['73a15be21da244448ba8820e4aa531f2', 'ece928fe8fa15bfae1672071765e419c', '$ecretf0rt3st'], true)) {
            $errors[] = 'APP_SECRET must not reuse a committed development or test value.';
        }

        $database = parse_url($this->databaseUrl);
        if (false === $database || !in_array($database['scheme'] ?? null, ['postgres', 'postgresql'], true)) {
            $errors[] = 'DATABASE_URL must be a valid PostgreSQL URL.';
        } else {
            if (in_array($database['host'] ?? '', ['127.0.0.1', 'localhost'], true)) {
                $errors[] = 'DATABASE_URL must not point to localhost.';
            }

            if ('' === ($database['user'] ?? '') || '' === ($database['pass'] ?? '') || '/' === ($database['path'] ?? '/')) {
                $errors[] = 'DATABASE_URL must include database credentials and a database name.';
            }

            if ('!ChangeMe!' === ($database['pass'] ?? '')) {
                $errors[] = 'DATABASE_URL must not use placeholder credentials.';
            }
        }

        if ('' === trim($this->mailerDsn) || str_starts_with($this->mailerDsn, 'null://')) {
            $errors[] = 'MAILER_DSN must configure a real production transport.';
        }

        if (false === filter_var($this->mailerFromAddress, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'MAILER_FROM_ADDRESS must be a valid email address.';
        }

        if ('' === trim($this->mailerFromName)) {
            $errors[] = 'MAILER_FROM_NAME must not be empty.';
        }

        $baseUrl = parse_url($this->appBaseUrl);
        if (
            false === $baseUrl
            || 'https' !== ($baseUrl['scheme'] ?? null)
            || '' === ($baseUrl['host'] ?? '')
            || !in_array($baseUrl['path'] ?? '', ['', '/'], true)
            || isset($baseUrl['user'])
            || isset($baseUrl['pass'])
            || isset($baseUrl['query'])
            || isset($baseUrl['fragment'])
        ) {
            $errors[] = 'APP_BASE_URL must be an HTTPS origin without credentials, a path, query, or fragment.';
        }

        return $errors;
    }
}
