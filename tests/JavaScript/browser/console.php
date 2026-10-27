<?php

require dirname(__DIR__, 3).'/vendor/autoload.php';
require __DIR__.'/Kernel.php';

use App\Tests\JavaScript\Browser\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

exit((new Application(new Kernel('browser_tests', true)))->run());
