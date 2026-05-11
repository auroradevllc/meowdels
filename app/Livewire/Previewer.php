<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class Previewer extends Component
{
    public $fileUrl;
    public $fileType;

    public function mount($fileUrl, $fileType): void
    {
        $this->fileUrl = $fileUrl;
        $this->fileType = $fileType;
    }

    #[On('preview-file')]
    public function updatePreview($url, $type): void
    {
        $this->fileUrl = $url;
        $this->fileType = $type;
    }
}
