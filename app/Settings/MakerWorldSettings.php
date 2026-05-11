<?php

namespace App\Settings;

use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

class MakerWorldSettings extends Settings
{
    public bool $enabled;

    #[ShouldBeEncrypted]
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
