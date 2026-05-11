<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('makerworld.enabled', false);
        $this->migrator->addEncrypted('makerworld.token', '');
    }
};
