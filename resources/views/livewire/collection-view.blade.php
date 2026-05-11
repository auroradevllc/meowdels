<?php

use App\Models\Collection;
use App\Models\PrintModel;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;

new class extends Component {

    // Track search in the URL query string
    public $collections;

    public ?Collection $activeCollection;

    // Sort options
    public $sort = 'latest';

    public string $editingName;

    public function mount(?Collection $collection): void
    {
        $this->collections = Auth::user()->collections()->withCount('items')
            ->get();

        if ($this->activeCollection) {
            $this->editingName = $this->activeCollection->name;
        }
    }

    public function collectionCreated($slug): void
    {
        $this->redirectRoute('collections.view', $slug);
    }

    public function saveName(): void
    {
        $this->validate([
            'editingName' => 'required|string|max:255',
        ]);

        $this->activeCollection->fill([
            'name' => $this->editingName,
        ]);

        $this->activeCollection->save();

        $this->redirectRoute('collections.view', $this->activeCollection->slug);
    }
}; ?>
<div class="flex flex-col md:flex-row gap-8 min-h-[600px]">
    <aside class="w-full md:w-64 shrink-0">
        <flux:navlist variant="sidebar">
            <flux:heading size="sm" class="px-3 mb-2">Your Collections</flux:heading>

            @forelse($collections as $collection)
                <flux:navlist.item :href="route('collections.view', $collection->slug)"
                    :current="$activeCollection && $activeCollection->id === $collection->id"
                    class="cursor-pointer"
                >
                    {{ $collection->name }}
                    <flux:badge size="sm" inset="bottom" variant="subtle" class="ml-auto">
                        {{ $collection->items_count }}
                    </flux:badge>
                </flux:navlist.item>
            @empty
                <div class="px-3 py-2 text-sm text-zinc-500 italic">
                    No collections yet.
                </div>
            @endforelse

            <flux:separator variant="subtle" class="my-2" />

            <flux:modal.trigger name="create-collection">
                <flux:navlist.item icon="plus-circle">Create Collection</flux:navlist.item>
            </flux:modal.trigger>
        </flux:navlist>
    </aside>

    <!-- Right Content Area -->
    <main class="flex-1">
        @if($activeCollection)
            <div class="space-y-6">
                <livewire:model-gallery
                    :heading-title="$activeCollection->name"
                    :collection-id="$activeCollection->id"
                    :key="'gallery-'.$activeCollection->id"
                >
                    <div class="flex items-center translate-y-[2px]">
                        <flux:dropdown>
                            <flux:button
                                variant="ghost"
                                icon="ellipsis-horizontal"
                                size="sm"
                                square
                                class="text-zinc-400 hover:text-zinc-800"
                            />

                            <flux:menu>
                                <flux:modal.trigger name="rename-collection">
                                    <flux:menu.item icon="pencil-square">Rename</flux:menu.item>
                                </flux:modal.trigger>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete">
                                    Delete
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </livewire:model-gallery>
            </div>
        @else
            <div class="h-full flex flex-col items-center justify-center border-2 border-dashed border-zinc-200 rounded-xl p-12 text-center">
                <div class="bg-zinc-100 p-4 rounded-full mb-4">
                    <flux:icon.plus-circle class="size-10 text-zinc-400" />
                </div>

                <flux:heading size="lg">No collections found</flux:heading>
                <flux:subheading class="max-w-xs mx-auto mb-6">
                    Organize your 3D models into custom lists to keep your workspace tidy.
                </flux:subheading>

                <flux:modal.trigger name="create-collection">
                    <flux:button variant="primary">Create your first collection</flux:button>
                </flux:modal.trigger>
            </div>
        @endif
    </main>

    <livewire:collection-create @created="collectionCreated(event.detail.slug)" />

    <flux:modal name="rename-collection" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Rename Collection</flux:heading>
                <flux:subheading>Enter a new name for your collection.</flux:subheading>
            </div>

            <flux:input wire:model="editingName" label="New Name" />

            <div class="flex">
                <flux:spacer />
                <flux:button variant="ghost" x-on:click="$flux.modal('rename-collection').close()" class="mr-2">Cancel</flux:button>
                <flux:button type="submit" variant="primary" wire:click="saveName">Save Changes</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
