<?php

use Livewire\Component;

new class extends Component {
    public string $action = 'create';
    public string $text = 'Create';

    public string $newCollectionName;

    public function create(): void
    {
        $this->validate(['newCollectionName' => 'required|string|max:255']);

        $collection = auth()->user()->collections()->create([
            'name' => $this->newCollectionName,
        ]);

        $this->newCollectionName = '';

        // Closes the Flux modal via the Livewire helper
        $this->modal('create-collection')->close();

        $this->dispatch('created', id: $collection->id, slug: $collection->slug);
    }
};
?>
<flux:modal name="create-collection" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Create New Collection</flux:heading>
            <flux:subheading>Give your new collection a name.</flux:subheading>
        </div>

        <flux:input
            wire:model="newCollectionName"
            label="Name"
            placeholder="e.g. My Projects"
            wire:keydown.enter="{{ $action }}"
            autofocus
        />

        <div class="flex">
            <flux:spacer/>
            <flux:button variant="ghost" x-on:click="$flux.modal('create-collection').close()" class="mr-2">Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:click="{{ $action }}">{{ $text }}</flux:button>
        </div>
    </div>
</flux:modal>
