<div>
    <flux:dropdown>
        <flux:button icon="plus-circle" variant="outline">Add to Collection</flux:button>

        <flux:menu class="min-w-64">
            <flux:menu.heading>Your Collections</flux:heading>

            <div class="max-h-64 overflow-y-auto">
                @foreach($collections as $collection)
                    @php
                        $isAdded = in_array($collection->id, $currentCollectionIds);
                    @endphp
                    <flux:menu.item
                        wire:click="toggleCollection({{ $collection->id }})"
                        :icon="$isAdded ? 'minus-circle' : 'plus-circle'"
                    >
                        {{ $collection->name }}
                    </flux:menu.item>
                @endforeach
            </div>

            <flux:menu.separator />

            <flux:modal.trigger name="create-collection">
                <flux:menu.item icon="plus-circle">Create New Collection</flux:menu.item>
            </flux:modal.trigger>
        </flux:menu>
    </flux:dropdown>

    <livewire:collection-create @created="toggleCollection(event.detail.id)" />
</div>
