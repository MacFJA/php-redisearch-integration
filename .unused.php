<?php

$projectPath = __DIR__ . '/';

$scanDirectories = [
    $projectPath . '/src/',
];

$scanFiles = [];
$excludeDirectories = [];
return [
    /**
     * Required params
     **/
    'composerJsonPath' => $projectPath . '/composer.json',
    'vendorPath' => $projectPath . '/vendor/',
    'scanDirectories' => $scanDirectories,

    /**
     * Optional params
     **/
    'skipPackages' => [
        'phpmd/phpmd',// QA tools
        'insolita/unused-scanner',// QA tools
        'vimeo/psalm',// QA tools
        'friendsofphp/php-cs-fixer',// QA tools
        'rskuipers/php-assumptions',// QA tools
        'phan/phan',// QA tools
        'ergebnis/composer-normalize',// QA tools
        'enlightn/security-checker',// QA tools
        'jakub-onderka/php-parallel-lint',// QA tools
        'phpunit/phpunit',// Unit test
        'sebastian/phpcpd',// QA tools

        'symfony/polyfill-mbstring',// Prevent Symfony Polyfill requiring PHP 8
    ],
    'excludeDirectories' => $excludeDirectories,
    'scanFiles' => $scanFiles,
    'extensions' => ['*.php'],
    'requireDev' => true
];