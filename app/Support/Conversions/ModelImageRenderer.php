<?php

namespace App\Support\Conversions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Conversions\ImageGenerators\ImageGenerator;
use Symfony\Component\Process\ExecutableFinder;
use Http;

class ModelImageRenderer extends ImageGenerator
{
    /**
     * This function should return a path to an image representation of the given file.
     */
    public function convert(string $file, Conversion $conversion = null) : ?string
    {
        $fileContents = file_get_contents($file);

        $pngPath = pathinfo($file, PATHINFO_DIRNAME).'/'.pathinfo($file, PATHINFO_FILENAME).'.png';

        $response = Http::attach(
            'stl',             // This must match the name in upload.single('stl')
            $fileContents,
            basename($file)
        )->sink($pngPath)
        ->post('http://localhost:3000/render');

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return $pngPath;
    }

    public function requirementsAreInstalled() : bool
    {
        try {
            $res = Http::get('http://localhost:3000/ping');

            return $res->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function supportedExtensions() : Collection
    {
        return collect(['stl', '3mf']);
    }

    public function supportedMimeTypes() : Collection
    {
        return collect([
            'application/octet-stream',
        ]);
    }
}
