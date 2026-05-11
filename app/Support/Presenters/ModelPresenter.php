<?php

namespace App\Support\Presenters;

use Laracasts\Presenter\Presenter;

class ModelPresenter extends Presenter
{
    public function sourceName(): string
    {
        $host = parse_url($this->url, PHP_URL_HOST);
        $host = preg_replace('/^www\./', '', $host);

        return match ($host) {
            'thingiverse.com' => __('Thingiverse'),
            'printables.com' => __('Printables'),
            default => $host,
        };
    }



    public function availableFileTypes(): string
    {
        return $this->media->pluck('extension')->unique()
            ->filter(function ($item) {
                return in_array(strtolower($item), config('meowdels.file_types'));
            })->join(', ');
    }

    public function authorName(): ?string
    {
        return $this->authors->first()->name;
    }
}
