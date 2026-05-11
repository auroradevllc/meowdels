<?php

namespace App\Livewire;

use App\Support\Attributes\Description;
use Flux;
use Livewire\Component;
use ReflectionClass;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;

class Settings extends Component
{
    public string $settingsClass;

    public string $settingsName;

    public array $encrypted = [];

    public array $descriptions = [];

    public array $state = [];

    public function mount(string $group): void
    {
        $this->settingsClass = $this->getClassFromGroup($group);

        $cl = new ReflectionClass($this->settingsClass);

        $this->settingsName = str($cl->getShortName())->headline();

        $this->encrypted = call_user_func([$this->settingsClass, 'encrypted']);

        foreach ($cl->getProperties() as $property) {
            $at = $property->getAttributes(ShouldBeEncrypted::class);

            if ($at) {
                $this->encrypted[] = $property->getName();
            }

            $descr = $property->getAttributes(Description::class);

            if ($descr) {
                $data = $descr[0]->newInstance();

                $this->descriptions[$property->getName()] = $data->description;
            }
        }

        // Resolve the settings instance from the container
        $settings = app($this->settingsClass);

        // Convert the settings object to an array for the form state
        $this->state = $settings->toArray();
    }

    protected function getClassFromGroup(string $group)
    {
        // Path where your settings live
        $path = app_path('Settings');

        return collect(config('settings.settings'))
            ->first(function ($class) use ($group) {
                return $class::group() === $group;
            });
    }

    public function save()
    {
        $settings = app($this->settingsClass);

        if (method_exists($settings, 'rules')) {
            $validator = \Validator::make($this->state, $settings->rules());

            if ($validator->fails()) {
                $messages = collect($validator->errors()->getMessages())->map(fn ($arr) => reset($arr));

                $messages->each(fn ($value, $key) => $this->addError('state.'.$key, $value));

                Flux::toast('Failed to update configuration.', variant: 'danger');

                return;
            }
        }

        // Dynamically fill and save
        $settings->fill($this->state);

        $settings->save();

        Flux::toast('Configuration updated successfully.');
    }
}
