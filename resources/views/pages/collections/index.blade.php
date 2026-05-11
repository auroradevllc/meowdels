<?php

use App\Models\Collection;
use Livewire\Component;

new class extends Component {
    public ?Collection $activeCollection;

    public function mount(): void
    {
        $this->activeCollection = Auth::user()->collections()->withCount('items')
            ->first();
    }
};
?>

<div>
    <livewire:collection-view :active-collection="$activeCollection"/>
</div>
