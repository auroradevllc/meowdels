<?php

namespace App\Settings;

use App\Support\Attributes\Description;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

class MakerWorldSettings extends Settings
{
    #[Description('Enable/Disable MakerWorld Importer')]
    public bool $enabled;

    #[ShouldBeEncrypted]
    #[Description('The `token` cookie from MakerWorld, use your browser Storage tab to find it')]
    public string $token;

    public static function group(): string
    {
        return 'makerworld';
    }

    public function rules(): array
    {
        return [
            'token' => ['required_if_accepted:enabled'],
        ];
    }
}
