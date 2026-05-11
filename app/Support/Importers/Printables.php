<?php

namespace App\Support\Importers;

use App\Models\PrintModel;
use App\Settings\PrintablesSettings;
use File;
use Http;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\DomCrawler\Crawler;
use ZipArchive;

#[SettingsClass(PrintablesSettings::class)]
class Printables extends Importer
{
    public function importModel(string $url): ?PrintModel
    {
        $licenses = $this->retrievePrintablesLicenses();

        $this->importer->updateStatus('Retrieving data');

        $modelData = $this->retrieveModelData($url);

        $converter = new HtmlConverter([
            'strip_tags' => true,
            'hard_break' => true,
        ]);

        $modelData->description = $converter->convert($modelData->description);
        $modelData->summary = $converter->convert($modelData->summary);

        $license = 'unknown';

        if ($licenseData = $licenses[$modelData->license->id]) {
            $license = strtolower($licenseData->abbreviation);
        } else {
            $license = 'cc-by-sa';
        }

        $model = PrintModel::create([
            'name' => $modelData->name,
            'description' => $modelData->description ?? $modelData->summary,
            'license' => $license,
            'url' => $url,
        ]);

        if ($modelData->user) {
            $model->authors()->create([
                'name' => $modelData->user->handle,
            ]);
        }

        if ($modelData->tags) {
            $model->attachTags(collect($modelData->tags)->pluck('name'));
        }

        $this->importer->updateStatus('Attaching images');

        if (! empty($modelData->images)) {
            foreach ($modelData->images as $image) {
                $model->addMediaFromUrl('https://media.printables.com/'.$image->filePath)
                    ->toMediaCollection('images');
            }
        }
        $this->importer->updateStatus('Retrieving model files');

        $files = $this->retrievePrintableModelFiles($modelData->id);

        $tempFile = $this->downloadPackFile($modelData->id, $files->id, 'pack');

        $this->importer->updateStatus('Extracting files');

        try {
            $zip = new ZipArchive;

            if ($zip->open($tempFile)) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $file = $zip->getNameIndex($i);

                    if (str_ends_with($file, '/')) {
                        continue;
                    }

                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                    if (! in_array($ext, config('meowdels.file_types'))) {
                        continue;
                    }

                    $stream = $zip->getStreamIndex($i);

                    $model->addMediaFromStream($stream)
                        ->usingFileName($file)
                        ->toMediaCollection('3d-files');
                }

                $zip->close();
            }
        } finally {
            File::delete($tempFile);
        }
        $this->importer->updateStatus('Done');

        return $model;
    }

    private function retrieveModelData($url)
    {
        $res = Http::get($url);

        if ($res->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve data');
        }

        $crawler = new Crawler((string) $res->getBody());

        $scripts = collect($crawler->filter('script[data-url*="https://api.printables.com/graphql/"]')->each(function (Crawler $node) {
            return $node->text();
        }))->map(function ($text) {
            $graph = json_decode($text);

            if (! $graph) {
                return null;
            }

            // Decode script content (json)
            return json_decode($graph->body);
        })->filter()->values();

        $modelData = $scripts->first(function ($obj) {
            return ! empty($obj->data->model);
        });

        if (! $modelData) {
            throw new \Exception('Failed to find model data in page');
        }

        return $modelData->data->model;
    }

    private function retrievePrintablesLicenses(): Collection
    {
        $query = <<<'QUERY'
query Licenses {
  licenses {
      id
      name
      abbreviation
      content
      disallowRemixing
      freeModels
      storeModels
      allowedLicensesAfterRemixing {
          id
          __typename
      }
      __typename
  }
}
QUERY;

        $json = $this->executeGraphQL('Licenses', $query);

        return collect($json->licenses)->keyBy('id');
    }

    private function retrievePrintableModelFiles($id)
    {
        $query = <<<'QUERY'
query ModelFiles($id: ID!) {
  model: print(id: $id) {
    id
    filesType
    gcodes {
      ...GcodeDetail
      __typename
    }
    stls {
      ...StlDetail
      __typename
    }
    slas {
      ...SlaDetail
      __typename
    }
    otherFiles {
      ...OtherFileDetail
      __typename
    }
    downloadPacks {
      id
      name
      fileSize
      fileType
      __typename
    }
    __typename
  }
}
fragment GcodeDetail on GCodeType {
  id
  created
  name
  folder
  note
  printer {
    id
    name
    __typename
  }
  excludeFromTotalSum
  printDuration
  layerHeight
  nozzleDiameter
  material {
    id
    name
    __typename
  }
  weight
  fileSize
  filePreviewPath
  rawDataPrinter
  order
  __typename
}
fragment OtherFileDetail on OtherFileType {
  id
  created
  name
  folder
  note
  fileSize
  filePreviewPath
  order
  __typename
}
fragment SlaDetail on SLAType {
  id
  created
  name
  folder
  note
  expTime
  firstExpTime
  printer {
    id
    name
    __typename
  }
  printDuration
  layerHeight
  usedMaterial
  fileSize
  filePreviewPath
  order
  __typename
}
fragment StlDetail on STLType {
  id
  created
  name
  folder
  note
  fileSize
  filePreviewPath
  order
  __typename
}
QUERY;

        $json = $this->executeGraphQL('ModelFiles', $query, [
            'id' => $id,
        ]);

        if (empty($json) || empty($json->model)) {
            throw new \Exception('Failed to retrieve printables model file data');
        }

        return collect($json->model->downloadPacks)->first(function ($pack) {
            return $pack->fileType == 'MODEL_FILES';
        });
    }

    private function downloadPackFile(string $modelId, string $fileId, string $fileType, string $source = 'model_detail')
    {
        $query = <<<'QUERY'
mutation GetDownloadLink($id: ID!, $modelId: ID!, $fileType: DownloadFileTypeEnum!, $source: DownloadSourceEnum!) {
  getDownloadLink(
    id: $id
    printId: $modelId
    fileType: $fileType
    source: $source
  ) {
    ok
    errors {
      ...Error
      __typename
    }
    output {
      link
      count
      ttl
      __typename
    }
    __typename
  }
}
fragment Error on ErrorType {
  field
  messages
  __typename
}
QUERY;

        $json = $this->executeGraphQL('GetDownloadLink', $query, [
            'fileType' => $fileType,
            'id' => $fileId,
            'modelId' => $modelId,
            'source' => $source,
        ]);

        $output = $json->getDownloadLink->output;

        if (empty($output)) {
            throw new \Exception('Failed to retrieve download link');
        }

        $tempPath = storage_path('app/temp/'.uniqid().'.zip');

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        Http::sink($tempPath)
            ->get($output->link);

        return $tempPath;
    }

    private function executeGraphQL(string $operation, string $query, array $variables = [])
    {
        $body = [
            'operationName' => $operation,
            'query' => $query,
        ];

        if (! empty($variables)) {
            $body['variables'] = $variables;
        }
        $res = Http::withHeaders([
            'Content-Type' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:149.0) Gecko/20100101 Firefox/149.0',
            'Accept' => 'application/graphql-response+json, application/graphql+json, application/json, text/event-stream, multipart/mixed',
            'graphql-client-version' => 'v4.5.0',
            'Origin' => 'https://www.printables.com',
        ])->asJson()
            ->post('https://api.printables.com/graphql/', $body);

        if ($res->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve '.$operation.', status: '.$res->getStatusCode().', body: '.$res->body());
        }

        return json_decode($res->body())->data;
    }

    public static function domains(): array
    {
        return [
            'printables.com',
        ];
    }

    public function validateSettings(): ?MessageBag
    {
        $validator = \Validator::make($this->settings->toArray(), [
            'enabled' => ['required', 'accepted'],
        ]);

        return $validator->errors();
    }
}
