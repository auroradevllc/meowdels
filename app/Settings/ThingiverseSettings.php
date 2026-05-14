<?php

namespace App\Settings;

use App\Support\Attributes\Description;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

class ThingiverseSettings extends Settings
{

    #[Description('Enable/Disable Thingiverse Importer')]
    public bool $enabled;

    #[ShouldBeEncrypted]
    #[Description('Your Thingiverse API Key, get one here: <a href="https://www.thingiverse.com/developers/my-apps">Developer Console</a>')]
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
