<?php

use Symfony\Component\Finder\Finder;

$modules = [];
$files = (new Finder())->files()->in(dirname(__DIR__).'/modules')
    ->exclude('fixtures')->name('*.test.js')->sortByName();
$selected = getenv('DOGS_DIARY_TEST_FILE');
foreach ($files as $file) {
    if ($selected && 'tests/JavaScript/modules/'.$file->getRelativePathname() !== $selected) {
        continue;
    }
    $modules[] = 'tests/modules/'.$file->getRelativePathname();
}

return $modules;
