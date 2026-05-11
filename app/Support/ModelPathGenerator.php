<?php
namespace App\Support;

use App\Models\PrintModel;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ModelPathGenerator implements PathGenerator
{
    /*
     * Get the path for the given media, relative to the root storage path.
     */
    public function getPath(Media $media): string
    {
        // Check if the media belongs to a PrintModel
        if ($media->model_type === PrintModel::class) {
            return 'models/' . $media->model->id . '/' . $media->id . '/';
        }

        return $media->id . '/';
    }

    /*
     * Get the path for conversions.
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'conversions/';
    }

    /*
     * Get the path for responsive images.
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'responsive/';
    }
}
