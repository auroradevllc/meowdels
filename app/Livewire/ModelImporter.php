<?php

namespace App\Livewire;

use App\Models\PrintModel;
use App\Support\Importers\Importer;
use File;
use GuzzleHttp\Client;
use Livewire\Component;
use Livewire\WithFileUploads;

class ModelImporter extends Component
{
    use WithFileUploads;

    public string $url;

    public array $renderImages = [];

    private Client $client;

    private array $importers;

    public string $status = 'Import Model';

    public function __construct()
    {

        $this->importers = $this->buildImporterMap();
    }

    protected function buildImporterMap(): array
    {
        // Path where your settings live
        $path = app_path('Support/Importers');

        $classes = collect(File::allFiles($path))
            ->map(fn ($file) => 'App\\Support\\Importers\\'.$file->getBasename('.php'))
            ->filter(fn ($class) => is_subclass_of($class, Importer::class));

        $result = [];

        foreach ($classes as $class) {
            $instance = new $class($this);

            foreach ($class::domains() as $domain) {
                $result[$domain] = $instance;
            }
        }

        return $result;
    }

    public function import()
    {
        $this->validate([
            'url' => [
                'required',
                'url',
            ],
        ]);

        $host = parse_url($this->url, PHP_URL_HOST);
        $host = preg_replace('/^www\./', '', $host);

        if (! array_key_exists($host, $this->importers)) {
            $this->addError('url', 'URL is not supported');

            return;
        }

        /**
         * @var $importer Importer
         */
        $importer = $this->importers[$host];

        $errors = $importer->validateSettings();

        if ($errors && $errors->isNotEmpty()) {
            if ($errors->has('enabled')) {
                $this->addError('url', 'The importer for '.$host.' is not enabled.');

                return;
            }

            $this->addError('url', 'Importer is not available. '.$errors->first());

            return;
        }

        try {
            $model = $importer->importModel($this->url);

            if (! $model) {
                $this->addError('url', 'Unable to import model due to unknown error');

                return;
            }
        } catch (\Exception $e) {
            $this->addError('url', 'Unable to import model due to error: '.$e->getMessage());

            return;
        }

        $this->updateStatus('Files imported, rendering previews');

        $this->dispatch('import-render', [
            'uuid' => $model->uuid,
            'files' => $model->getMedia('3d-files')->map(fn ($file) => [
                'url' => $file->getUrl(),
                'name' => $file->file_name,
            ]),
        ]);
    }

    public function handleRenderUploads($id)
    {
        $model = PrintModel::findOrFail($id);

        foreach ($this->renderImages as $image) {
            $model->addMedia($image->getRealPath())
                ->usingFileName($image->getClientOriginalName())
                ->toMediaCollection('renders');
        }

        $this->updateStatus('Render complete');

        return redirect()->route('models.view', $model->uuid);
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;
        $this->stream(content: $status, replace: true, to: 'import-status');
    }
}
