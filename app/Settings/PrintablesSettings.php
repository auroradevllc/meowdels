<?php

namespace App\Settings;

use App\Support\Attributes\Description;
use Spatie\LaravelSettings\Settings;

class PrintablesSettings extends Settings
{
    #[Description('Enable/Disable Printables importing')]
    public bool $enabled;

    public static function group(): string
    {
        return 'printables';
    }
}
