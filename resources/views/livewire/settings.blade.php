<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __($settingsName) }}</flux:heading>

    <x-pages::settings.layout :heading="$settingsName" :subheading="__('Update your settings for ' . str_replace('Settings', '', $settingsName))">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            @foreach($state as $key => $value)
                <flux:field>
                    <flux:label>{{ str($key)->headline() }}</flux:label>

                    @if (! empty($descriptions[$key]))
                        <flux:description>{!! $descriptions[$key] !!}</flux:description>
                    @endif

                    @if(is_bool($value))
                        <flux:switch wire:model="state.{{ $key }}" />
                    @elseif(in_array($key, $encrypted))
                        <flux:input type="password" wire:model="state.{{ $key }}" />
                    @else
                        <flux:input wire:model="state.{{ $key }}" />
                    @endif

                    @error($key) <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>
            @endforeach

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-settings-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
