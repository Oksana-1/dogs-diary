<?php

use Symfony\Component\Finder\Finder;

$modules = [];
$files = (new Finder())->files()->in(dirname(__DIR__).'/modules')
    ->exclude('fixtures')->name('*.test.js')->sortByName();
foreach ($files as $file) {
    $modules[] = 'tests/modules/'.$file->getRelativePathname();
}

return $modules;
