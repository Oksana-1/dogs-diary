<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => '@symfony/stimulus-bundle/loader.js',
    ],
    // Full browser build: one self-contained file that includes the runtime
    // template compiler, so island components can use `template: '...'` strings
    // without any Node build step. Unlike the esm-bundler build it does not
    // reference compile-time flags such as __VUE_OPTIONS_API__, so nothing has
    // to define them. For production you may switch to
    // 'vue/dist/vue.esm-browser.prod.js' (smaller, no dev warnings).
    'vue' => [
        'version' => '3.5.40',
        'package_specifier' => 'vue/dist/vue.esm-browser.js',
    ],
];
