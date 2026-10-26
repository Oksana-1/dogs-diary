<?php

$application = require dirname(__DIR__, 3).'/importmap.php';

return [
    'browser-tests' => ['path' => './runner.js', 'entrypoint' => true],
    'vue' => $application['vue'],
    '@vue/test-utils' => [
        'version' => '2.4.6',
        'package_specifier' => '@vue/test-utils/dist/vue-test-utils.esm-browser.js',
    ],
    'qunit' => [
        'version' => '2.24.1',
        'package_specifier' => 'qunit/qunit/qunit.js',
    ],
];
