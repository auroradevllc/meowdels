<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('thingiverse.enabled', false);
        $this->migrator->addEncrypted('thingiverse.key', '');
    }
};
