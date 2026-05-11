<?php

namespace App\Settings;

use App\Support\Attributes\Description;
use Spatie\LaravelSettings\Settings;

class GlobalSettings extends Settings
{
    #[Description('Enable registration for new users')]
    public bool $registration;

    public static function group(): string
    {
        return 'global';
    }
}
