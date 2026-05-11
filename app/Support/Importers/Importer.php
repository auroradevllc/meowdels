<?php

namespace App\Support\Importers;

use App\Livewire\ModelImporter;
use App\Models\PrintModel;
use Illuminate\Support\MessageBag;
use ReflectionClass;
use Spatie\LaravelSettings\Settings;

abstract class Importer
{
    protected ModelImporter $importer;

    protected ?Settings $settings = null;

    public function __construct(ModelImporter $importer)
    {
        $this->importer = $importer;

        $this->resolveSettings();
    }

    private function resolveSettings(): void
    {
        $cl = new ReflectionClass($this);

        $attribute = collect($cl->getAttributes(SettingsClass::class))->first();

        if ($attribute) {
            $settingsDef = $attribute->newInstance();

            if ($settingsDef->className == $cl->getName()) {
                throw new \Exception('Cannot use the importer class as the settings class');
            }

            $this->settings = app($settingsDef->className);
        }
    }

    public function validateSettings(): ?MessageBag
    {
        return null;
    }

    abstract public function importModel(string $url): ?PrintModel;

    abstract public static function domains(): array;
}
