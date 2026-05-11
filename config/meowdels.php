<?php

use App\Support\Importers\MakerWorld;
use App\Support\Importers\Printables;
use App\Support\Importers\Thingiverse;

return [
    'file_types' => [
        'step',
        'obj',
        'stl',
        '3mf',
    ],

    'license_map' => [
        'non-commercial-no-derivatives' => 'cc-by-nc-nd',
        'non-commercial-share-alike' => 'cc-by-nc-sa',
        'non-commercial' => 'cc-by-nc',
        'no-derivatives' => 'cc-by-nd',
        'share-alike' => 'cc-by-sa',
        'attribution' => 'cc-by',
        'public-domain-dedication' => 'cc-0',
        'gnu-gpl' => 'gpl',
        'gnu-lgpl' => 'lgpl',
        'bsd-license' => 'bsd',
    ],

    'importers' => [
        MakerWorld::class,
        Printables::class,
        Thingiverse::class,
    ],

    'flaresolverr' => [
        'url' => env('FLARESOLVERR_URL', 'http://flaresolverr:8129')
    ],

    'slicers' => [
        'cura' => 'Ultimaker Cura',
        'orcaslicer' => 'OrcaSlicer',
        'prusaslicer' => 'PrusaSlicer',
        'bambustudio' => 'Bambu Studio',
    ],
];
