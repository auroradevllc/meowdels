<?php

use App\Models\Collection;
use Livewire\Component;

new class extends Component {
    public Collection $collection;

    public function mount(Collection $collection)
    {
        $this->collection = $collection;
    }
};
?>

<div>
    <livewire:collection-view :active-collection="$collection" />
</div>
