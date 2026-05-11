<?php

namespace App\Livewire;

use App\Models\PrintModel;
use Livewire\Component;

class ModelViewer extends Component
{
    public PrintModel $model;

    public $activeUrl;

    public $activeType;

    public function mount(PrintModel $model)
    {
        $this->model = $model;

        // Default the active image to the first one in the collection
        $this->activeUrl = $model->getFirstMediaUrl('images');
        $this->activeType = 'image';

        if (empty($this->activeUrl)) {
            $firstModel = $model->getFirstMedia('3d-files');

            $this->activeUrl = $firstModel->getUrl();
            $this->activeType = 'stl';
        }
    }
}
