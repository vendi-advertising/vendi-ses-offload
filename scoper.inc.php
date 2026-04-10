<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
    'prefix' => 'Vendi\\SesOffload\\Vendor',

    'finders' => [
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->in('vendor'),
        // Include composer files so dump-autoload works in the build dir.
        Finder::create()
            ->files()
            ->name(['composer.json', 'composer.lock'])
            ->depth(0)
            ->in('.'),
    ],

    'exclude-namespaces' => [
        'Vendi\SesOffload',
    ],

    'exclude-files' => [],

    'patchers' => [],
];
