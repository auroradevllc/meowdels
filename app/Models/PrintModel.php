<?php

namespace App\Models;

use App\Concerns\Collectable;
use App\Support\Presenters\ModelPresenter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Laracasts\Presenter\PresentableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\Attributes\Sluggable;
use Spatie\Tags\HasTags;

#[Fillable(
    'name',
    'description',
    'author',
    'license',
    'url',
    'is_public',
)]
#[Sluggable(from: 'name', to: 'slug')]
class PrintModel extends Model implements HasMedia
{
    use Collectable, HasTags, InteractsWithMedia, PresentableTrait;

    protected $presenter = ModelPresenter::class;

    public function authors(): HasMany
    {
        return $this->hasMany(PrintModelAuthor::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
        $this->addMediaCollection('3d-files');
        $this->addMediaCollection('renders');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections('images')
            ->width(368)
            ->height(232)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->performOnCollections('renders')
            ->width(368)
            ->height(232)
            ->sharpen(10)
            ->nonQueued();
    }

    public function getRenderUrl(Media $file): ?string
    {
        // Support server sided renders
        if (in_array('render', $file->getMediaConversionNames()) && $file->hasGeneratedConversion('render')) {
            return $file->getUrl('render');
        }

        // Fall back to using client side renders
        $renderName = pathinfo($file->file_name, PATHINFO_FILENAME).'.png';

        // Look for the media item in the same collection with that specific filename
        $render = $this->getFirstMedia('renders', fn ($media) => $media->file_name === $renderName);

        return $render ? $render->getUrl() : null;
    }

    public function previewableMedia(): Collection
    {
        return $this->media->filter(function (Media $file) {
            if ($file->collection_name === 'renders') {
                return false;
            }

            $extension = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));

            if (in_array($extension, ['stl', '3mf'])) {
                return true;
            }

            if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif'])) {
                return true;
            }

            return false;
        })->sortBy(function ($file) {
            $extension = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));

            // Assign a weight: Images (0), 3D Files (1)
            if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif'])) {
                return 0;
            }

            if (in_array($extension, ['stl', '3mf'])) {
                return 1;
            }

            return 2; // Fallback for anything else
        })->values();
    }
}
