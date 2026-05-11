<?php

namespace App\Settings;

use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

class ThingiverseSettings extends Settings
{

    public bool $enabled;

    #[ShouldBeEncrypted]
    public string $key;

    public static function group(): string
    {
        return 'thingiverse';
    }

    public function rules(): array
    {
        return [
            'key' => ['required_if_accepted:enabled'],
        ];
    }
}
