<?php

use App\Models\PrintModel;
use Livewire\Component;

new class extends Component {
    public PrintModel $model;

    public function mount(PrintModel $model)
    {
        $this->model = $model;
    }
};
?>

<div>
    <livewire:model-viewer :model="$model"/>
</div>
