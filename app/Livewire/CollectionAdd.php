<?php

namespace App\Livewire;

use App\Models\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class CollectionAdd extends Component
{
    public $model; // The 3D Model, Product, etc.
    public $newCollectionName = '';
    public $showCreateInput = false;

    public function toggleCollection($collectionId): void
    {
        $collection = Auth::user()->collections()->findOrFail($collectionId);

        // Check if item is already in this collection
        $item = $collection->items()
            ->where('collectable_id', $this->model->id)
            ->where('collectable_type', get_class($this->model))
            ->first();

        if ($item) {
            $item->delete();
        } else {
            $collection->items()->create([
                'collectable_id' => $this->model->id,
                'collectable_type' => get_class($this->model),
            ]);
        }
    }

    public function createAndAdd(): void
    {
        $this->validate(['newCollectionName' => 'required|string|max:255']);

        $collection = auth()->user()->collections()->create([
            'name' => $this->newCollectionName,
        ]);

        $this->toggleCollection($collection->id);
        $this->newCollectionName = '';

        // Closes the Flux modal via the Livewire helper
        $this->modal('create-collection')->close();
    }

    public function render(): View
    {
        // This runs every time the component is refreshed or an event is heard.
        return view('livewire.collection-add', [
            'collections' => Auth::user()->collections()->get(),
            'currentCollectionIds' => $this->model->collections()->pluck('collection_id')->toArray(),
        ]);
    }
}
