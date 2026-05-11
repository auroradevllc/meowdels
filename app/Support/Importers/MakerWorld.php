<?php

namespace App\Support\Importers;

use App\Models\PrintModel;
use App\Settings\MakerWorldSettings;
use Http;
use Illuminate\Support\MessageBag;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\DomCrawler\Crawler;

#[SettingsClass(MakerWorldSettings::class)]
class MakerWorld extends Importer
{
    const string DOMAIN = 'makerworld.com';

    public function importModel(string $url): ?PrintModel
    {
        if (! preg_match('#/(en)/models/(.*?)($|\?)#', $url)) {
            throw new \Exception('Invalid Makerworld model page');
        }

        $this->importer->updateStatus('Attempting to fetch data using Flaresolverr');

        $solverUrl = rtrim(config('meowdels.flaresolverr.url'), '/');

        // Use Flaresolverr to retrieve the initial page
        $res = Http::post($solverUrl.'/v1', [
            'cmd' => 'request.get',
            'url' => $url,
        ]);

        // Decode into an object, superior to arrays
        $res = json_decode($res->body());

        if ($res->status !== 'ok') {
            throw new \Exception('Flaresolverr was unable to solve the Cloudflare challenge: '.$res->message);
        }

        // Solution $res->solution should be used for cookies + user data

        // We can use the response Flaresolverr retrieved here
        $crawler = new Crawler($res->solution->response);

        $data = $crawler->filter('#__NEXT_DATA__')->first();

        $json = json_decode($data->text());

        $this->importer->updateStatus('Creating model');

        $design = $json->props->pageProps->design;

        $converter = new HtmlConverter([
            'strip_tags' => true,
            'hard_break' => true,
        ]);

        $model = PrintModel::create([
            'name' => $design->title,
            'description' => $converter->convert($design->summary),
            'license' => $this->detectLicense($design->license),
            'url' => 'https://makerworld.com/en/models/'.$design->id.'-'.$design->slug,
        ]);

        if (! empty($design->designCreator)) {
            $model->authors()->create([
                'name' => $design->designCreator->name,
            ]);
        }

        if (! empty($design->tags)) {
            foreach ($design->tags as $tag) {
                $model->attachTag($tag);
            }
        }

        $this->importer->updateStatus('Downloading images');

        if (! empty($design->designExtension->design_pictures)) {
            foreach ($design->designExtension->design_pictures as $file) {
                // TODO use FlareSolverr response for images?
                $model->addMediaFromUrl($file->url)
                    ->usingFileName($file->name)
                    ->toMediaCollection('images');
            }
        }

        $tempPath = storage_path('app/temp/'.uniqid().'.zip');

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        $this->importer->updateStatus('Downloading model files');

        // These headers are required or we get a 403
        $req = Http::withHeaders([
            // These headers seem to be checked, so we have to set them.
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Encoding' => 'gzip, deflate',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Referer' => 'https://'.self::DOMAIN,
            'User-Agent' => $res->solution->userAgent, // Required, MUST set User-Agent to match FlareSolverr
        ]);

        // Attach cookie to download only
        $cookies = collect($data)->pluck('value', 'name')->all();
        $cookies['token'] = $this->settings->token;
        $req = $req->withCookies($cookies, self::DOMAIN);

        $url = 'https://'.self::DOMAIN.'/api/v1/design-service/design/'.$design->id.'/model';

        foreach ($design->designExtension->model_files as $file) {
            $fileReq = clone $req;

            $fileReq = $fileReq->withQueryParameters([
                'modelFileName' => '',
                'modelType' => $file->modelType,
                'key' => $file->unikey,
                'modelName' => $file->modelName,
                'type' => 'download',
            ]);

            $res = $fileReq->get($url);

            if ($res->getStatusCode() !== 200) {
                throw new \Exception('Unable to download file '.$file->modelName.': '.$res->getStatusCode().': '.$res->body());
            }

            $fileJson = json_decode($res->body());

            $model->addMediaFromUrl($fileJson->url)
                ->usingFileName($file->modelName)
                ->toMediaCollection('3d-files');
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
            'makerworld.com',
        ];
    }

    public function validateSettings(): ?MessageBag
    {
        $validator = \Validator::make($this->settings->toArray(), [
            'enabled' => ['required', 'accepted'],
            'token' => ['required'],
        ]);

        return $validator->errors();
    }
}
