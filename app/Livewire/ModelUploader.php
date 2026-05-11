<?php

namespace App\Livewire;

use App\Models\PrintModel;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use Livewire\WithFileUploads;

class ModelUploader extends Component
{
    use WithFileUploads;

    public string $name;

    public string $description = '';

    public string $license = '';

    public string $author = '';

    public $files = []; // Support for multiple direct files

    public function save()
    {
        $this->validate([
            'name' => 'required',
            'files' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    $has3dFile = collect($value)->contains(function ($file) {
                        return in_array($file->getClientOriginalExtension(), config('meowdels.file_types'));
                    });

                    if (! $has3dFile) {
                        $fail('You must upload at least one valid model file.');
                    }
                },
            ],
            'files.*' => [
                'required',
                'max:102400',
                'extensions:jpg,png,'.implode(',', config('meowdels.file_types')),
            ], // 100MB limit
        ]);

        /**
         * @var PrintModel $printModel
         */
        $printModel = PrintModel::create([
            'name' => $this->name,
            'license' => $this->license,
            'description' => $this->description,
        ]);

        if (!empty($this->author)) {
            $printModel->authors()->create([
                'name' => $this->author,
            ]);
        }

        /**
         * @var UploadedFile $file
         */
        foreach ($this->files as $file) {
            if (str_starts_with($file->getMimeType(), 'image/')) {
                $target = 'images';
                $name = $file->getClientOriginalName();

                if (str_ends_with($file->getClientOriginalName(), '-render.png')) {
                    $target = 'renders';
                    $name = preg_replace('/-render\.png$/', '.png', $name);
                }

                $printModel->addMedia($file->getRealPath())
                    ->usingFileName($name)
                    ->toMediaCollection($target);
            } else {
                $printModel->addMedia($file->getRealPath())
                    ->usingFileName($file->getClientOriginalName())
                    ->toMediaCollection('3d-files');
            }
        }

        return redirect()->route('models.view', $printModel->slug);
    }

    /**
     * Remove a file from the temporary uploads array.
     *
     * * @param int $index
     * @return void
     */
    public function removeFile($index)
    {
        // Check if the index exists to prevent errors
        if (isset($this->files[$index])) {
            // Remove the file from the array
            array_splice($this->files, $index, 1);
        }
    }

    public function getRenderUrl($file)
    {
        $renderName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '-render.png';

        // Look for the media item in the same collection with that specific filename
        $render = collect($this->files)->first(fn($media) => $media->getClientOriginalName() === $renderName);

        return $render ? $render->temporaryUrl() : null;
    }
}
