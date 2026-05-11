<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PrintablesSettings extends Settings
{
    public bool $enabled;

    public static function group(): string
    {
        return 'printables';
    }
}
