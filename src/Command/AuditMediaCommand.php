<?php

namespace App\Command;

use App\Application\Media\MediaStorageInterface;
use App\Repository\DogMediaRepository;
use App\Repository\TreatmentMediaRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:media:audit',
    description: 'Report missing media files and orphaned stored files.',
    aliases: ['app:dog-media:audit'],
)]
final class AuditMediaCommand extends Command
{
    public function __construct(
        private readonly DogMediaRepository $dogMediaRepository,
        private readonly TreatmentMediaRepository $treatmentMediaRepository,
        private readonly MediaStorageInterface $storage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'delete-orphans',
            null,
            InputOption::VALUE_NONE,
            'Delete stored files that have no matching database row.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $databaseKeys = array_merge(
            $this->dogMediaRepository->findAllStorageKeys(),
            $this->treatmentMediaRepository->findAllStorageKeys(),
        );
        $storedKeys = $this->storage->allStorageKeys();
        $missingKeys = array_values(array_filter(
            $databaseKeys,
            fn (string $key): bool => !$this->storage->exists($key),
        ));
        $orphanKeys = array_values(array_diff($storedKeys, $databaseKeys));

        $io->section('Database rows with missing files');
        $missingKeys ? $io->listing($missingKeys) : $io->writeln('None');

        $io->section('Stored files without database rows');
        $orphanKeys ? $io->listing($orphanKeys) : $io->writeln('None');

        if ($input->getOption('delete-orphans')) {
            foreach ($orphanKeys as $key) {
                $this->storage->delete($key);
            }

            $io->success(sprintf('Deleted %d orphaned file(s).', count($orphanKeys)));
        }

        if ([] !== $missingKeys) {
            $io->warning('Some media database rows point to missing files.');

            return Command::FAILURE;
        }

        $io->success('Media storage audit completed.');

        return Command::SUCCESS;
    }
}
