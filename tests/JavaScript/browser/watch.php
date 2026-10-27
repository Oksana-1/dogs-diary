<?php

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

function watchBrowserTests(string $root, ?string $testFile): int
{
    if (!function_exists('pcntl_async_signals')) {
        fwrite(STDERR, "Watch mode requires the PHP pcntl extension for clean interruption.\n");

        return 1;
    }

    $stopping = false;
    pcntl_async_signals(true);
    foreach ([SIGINT, SIGTERM] as $signal) {
        pcntl_signal($signal, static function () use (&$stopping): void { $stopping = true; });
    }
    $snapshot = static function () use ($root): string {
        $files = (new Finder())->files()->in([$root.'/assets/js', $root.'/tests/JavaScript'])->sortByName();
        $hashes = [];
        foreach ($files as $file) {
            $hashes[$file->getPathname()] = hash_file('sha256', $file->getPathname());
        }
        foreach (['bin/browser-tests', 'importmap.php', 'composer.lock'] as $path) {
            $hashes[$path] = hash_file('sha256', $root.'/'.$path);
        }

        return hash('sha256', serialize($hashes));
    };
    $command = [PHP_BINARY, $root.'/bin/browser-tests'];
    if (null !== $testFile) {
        array_push($command, '--file', $testFile);
    }
    $previous = null;
    $run = null;
    echo "Watching frontend sources and tests. Ctrl-C to stop.\n";
    try {
        while (!$stopping) {
            $current = $snapshot();
            if ($current !== $previous) {
                // Snapshot before running: edits during a run trigger the next run.
                $previous = $current;
                $run = new Process($command, $root);
                $run->setTimeout(null);
                $run->start(static function ($type, $output): void { echo $output; });
                while ($run->isRunning() && !$stopping) {
                    usleep(10000);
                }
                if (!$stopping) {
                    echo 'Run finished (exit '.$run->getExitCode()."). Waiting for changes…\n";
                }
            }
            usleep(250000);
        }
    } finally {
        // SIGINT lets the child remove its server, browser and temporary profile.
        $run?->stop(5, SIGINT);
    }

    return 130;
}
