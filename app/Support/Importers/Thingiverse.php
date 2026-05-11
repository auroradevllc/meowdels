<?php

namespace App\Support\Importers;

use App\Livewire\ModelImporter;
use App\Models\PrintModel;
use App\Settings\ThingiverseSettings;
use Http;
use Illuminate\Support\MessageBag;
use League\HTMLToMarkdown\HtmlConverter;

#[SettingsClass(ThingiverseSettings::class)]
class Thingiverse extends Importer
{

    public function importModel(string $url): ?PrintModel
    {
        $path = trim(parse_url($url, PHP_URL_PATH), '/');

        if (! preg_match('/^thing:(\d+)$/', $path, $matches)) {
            throw new \Exception('Thing is not a valid URL: '.$path);
        }

        $this->importer->updateStatus('Retrieving data');

        $res = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->settings->key,
        ])->get('https://api.thingiverse.com/things/'.$matches[1]);

        if ($res->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve data');
        }

        $json = json_decode($res->body());

        $this->importer->updateStatus('Creating model');

        $converter = new HtmlConverter([
            'strip_tags' => true,
            'hard_break' => true,
        ]);

        $model = PrintModel::create([
            'name' => $json->name,
            'description' => $converter->convert($json->description),
            'license' => $this->detectLicense($json->license),
            'url' => $json->public_url,
        ]);

        if (! empty($json->creator)) {
            $model->authors()->create([
                'name' => $json->creator->name,
            ]);
        }

        if (! empty($json->tags)) {
            foreach ($json->tags as $tag) {
                $model->attachTag($tag->name);
            }
        }

        $this->importer->updateStatus('Downloading files');

        if (! empty($json->zip_data->files)) {
            foreach ($json->zip_data->files as $file) {
                $model->addMediaFromUrl($file->url)
                    ->usingFileName($file->name)
                    ->toMediaCollection('3d-files');
            }
        }

        if (! empty($json->zip_data->images)) {
            foreach ($json->zip_data->images as $file) {
                $model->addMediaFromUrl($file->url)
                    ->usingFileName($file->name)
                    ->toMediaCollection('images');
            }
        }

        $this->importer->updateStatus('Done');

        return $model;
    }

    private function detectLicense($text)
    {
        $text = strtolower($text);
        $text = preg_replace('/[\s]/', '-', $text);

        foreach (config('meowdels.license_map') as $key => $license) {
            if (str_contains($text, $key)) {
                return $license;
            }
        }

        return 'cc-by-sa';
    }

    public static function domains(): array
    {
        return [
            'thingiverse.com',
        ];
    }

    public function validateSettings(): ?MessageBag
    {
        $validator = \Validator::make($this->settings->toArray(), [
            'enabled' => ['required', 'accepted'],
            'key' => ['required'],
        ]);

        return $validator->errors();
    }
}
