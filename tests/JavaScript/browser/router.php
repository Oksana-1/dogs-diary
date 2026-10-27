<?php

require dirname(__DIR__, 3).'/vendor/autoload.php';
require __DIR__.'/Kernel.php';

use App\Tests\JavaScript\Browser\Kernel;
use Symfony\Component\HttpFoundation\Request;

$kernel = new Kernel('browser_tests', true);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
