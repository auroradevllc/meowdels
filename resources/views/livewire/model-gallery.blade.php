<?php

use App\Models\PrintModel;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;

new class extends Component {

    use WithPagination;

    public string $headingTitle;

    public string $headingText;

    // Track search in the URL query string
    public $search = '';

    // Sort options
    public $sort = 'latest';

    public ?int $collectionId = null;

    public function with(): array {
        return [
            'printModels' => PrintModel::query()
                ->with(['media'])
                ->when($this->search, function (Builder $query) {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('author', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->sort, function (Builder $query) {
                    match ($this->sort) {
                        'latest' => $query->latest(),
                        'oldest' => $query->oldest(),
                        'alphabetical' => $query->orderBy('name', 'asc'),
                        default => $query->latest(),
                    };
                })
                ->when($this->collectionId, function(Builder $query) {
                    $query->whereHas('collections', function (Builder $query) {
                        $query->where('collection_id', $this->collectionId);
                    });
                })
                ->paginate(12),
        ];
    }

    public function deleteModel($id)
    {
        $model = PrintModel::find($id);

        if (!$model) {
            return;
        }
        // Optional: Check permissions (e.g., if the user owns this model)
        // $this->authorize('delete', $model);

        // Delete the model and its associated media
        $model->delete();

        // Flash a success message
        Flux::toast('Model deleted',
            heading: 'Model deleted.',
            variant: 'success',
        );

        // If this is a child component, you might need to refresh the parent
        // $this->dispatch('model-deleted');
    }

    // Reset pagination when searching or sorting
    public function updated($property) {
        if (in_array($property, ['search', 'sort'])) {
            $this->resetPage();
        }
    }

}; ?>

<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        @if ($headingTitle)
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1" class="leading-tight">
                    {{ $headingTitle }}
                </flux:heading>

                {{ $slot }}
            </div>
        @endif

        <div class="flex gap-2 w-full md:w-auto">
            <div class="flex-1 md:w-64">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Search..."
                    clearable
                />
            </div>

            <flux:select wire:model.live="sort" class="w-40">
                <option value="latest">Newest</option>
                <option value="oldest">Oldest</option>
                <option value="alphabetical">A-Z</option>
            </flux:select>
        </div>
    </div>
    <div class="relative">
        <div wire:loading
             class="absolute inset-0 z-10 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-[1px] rounded-xl flex items-center justify-center">
            <flux:icon.arrow-path class="animate-spin size-8 text-zinc-400"/>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($printModels as $model)
                <div
                    x-data
                    @click="window.location.href = '{{ route('models.view', $model->slug) }}'"
                    class="group relative flex flex-col overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 transition-all hover:shadow-lg hover:border-accent-500/50 cursor-pointer"
                >
                    <div class="aspect-square relative overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                        @if($model->hasMedia('images'))
                            <img src="{{ $model->getFirstMediaUrl('images', 'thumb') }}"
                                 class="h-full w-full object-cover transition-transform group-hover:scale-105">
                        @elseif($model->hasMedia('3d-files') && $model->getRenderUrl($model->getMedia('3d-files')->first()))
                            <img src="{{ $model->getRenderUrl($model->getMedia('3d-files')->first()) }}"
                                 class="h-full w-full object-cover transition-transform group-hover:scale-105">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-zinc-400">
                                <flux:icon.cube class="size-12"/>
                            </div>
                        @endif

                        <div class="absolute top-2 left-2 right-2 flex justify-between items-start">
                            <flux:badge size="sm" inset="top bottom">{{ $model->license }}</flux:badge>

                            <div @click.stop>
                                <flux:modal.trigger name="delete-model-{{ $model->id }}">
                                    <button class="p-1.5 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md rounded-lg text-zinc-500 hover:text-red-600 hover:bg-white dark:hover:bg-zinc-800 transition-colors shadow-sm">
                                        <flux:icon.trash variant="micro" />
                                    </button>
                                </flux:modal.trigger>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 flex flex-col flex-1">
                        <flux:heading size="lg" class="truncate">{{ $model->name }}</flux:heading>
                        @if(!$model->authors->isEmpty())
                            <flux:text size="sm" class="mb-4">by {{ $model->authors->first()->name }}</flux:text>
                        @endif

                        <div class="flex items-center justify-between mt-auto">
                            <flux:text size="xs" class="flex items-center gap-1">
                                <flux:icon.paper-clip variant="micro"/>
                                {{ $model->media->count() }} files
                            </flux:text>

                            <flux:icon.chevron-right variant="micro" class="text-zinc-400 group-hover:text-accent-500 transition-colors" />
                        </div>
                    </div>

                    <flux:modal name="delete-model-{{ $model->id }}" class="min-w-[22rem]">
                        <form wire:submit="deleteModel({{ $model->id }})" class="space-y-6">
                            <div>
                                <flux:heading size="lg">Delete Model?</flux:heading>
                                <flux:text>Are you sure you want to delete <strong>{{ $model->name }}</strong>? This action cannot be undone.</flux:text>
                            </div>

                            <div class="flex gap-2">
                                <flux:spacer />
                                <flux:modal.close>
                                    <flux:button variant="ghost">Cancel</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="danger" x-on:click="$modal.close();">Delete Model</flux:button>
                            </div>
                        </form>
                    </flux:modal>
                </div>
            @empty
                <div
                    class="col-span-full py-20 flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-zinc-200 dark:border-zinc-800 text-center">
                    <flux:icon.magnifying-glass class="size-10 text-zinc-300 mb-2"/>
                    <flux:heading>No models matched your criteria</flux:heading>
                    <flux:text>Try a different search term or check back later.</flux:text>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-12">
        {{ $printModels->links() }}
    </div>
</div>
