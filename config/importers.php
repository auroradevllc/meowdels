<?php

return [
    /**
     * Global importer options
     */
    'global' => [
        // Proxy server to use, such as Flaresolverr
        // Note: This can be set on a per-importer basis as well
        'proxy' => env('IMPORTER_PROXY'),
    ],

    // Thingiverse settings
    'thingiverse' => [
        'enabled' => env('THINGIVERSE_ENABLED', true),
        'key' => env('THINGIVERSE_KEY'),
    ],

    // Printables settings
    'printables' => [
        'enabled' => env('PRINTABLES_ENABLED', true),
    ],

    // MakerWorld settings
    'makerworld' => [
        'enabled' => env('MAKERWORLD_ENABLED', true),
        'token' => env('MAKERWORLD_TOKEN'),
    ],
];
