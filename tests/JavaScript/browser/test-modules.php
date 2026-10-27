<?php

use Symfony\Component\Finder\Finder;

$modules = [];
$files = (new Finder())->files()->in(dirname(__DIR__))
    ->exclude(['fixtures', 'helpers', 'browser'])->name('*.test.js')->sortByName();
$selected = getenv('DOGS_DIARY_TEST_FILE');
foreach ($files as $file) {
    $relativePath = $file->getRelativePathname();
    if (!str_starts_with($relativePath, 'core/') && !str_starts_with($relativePath, 'modules/')) {
        continue;
    }
    if ($selected && 'tests/JavaScript/'.$relativePath !== $selected) {
        continue;
    }
    $modules[] = 'tests/'.$relativePath;
}

return $modules;
