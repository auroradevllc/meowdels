<?php

namespace App\Http\Controllers;

use App\Models\PrintModel;
use Spatie\MediaLibrary\Support\MediaStream;

class DownloadController extends Controller {

    public function download(PrintModel $model) {
        $modelFiles = $model->getMedia('3d-files');
        $images = $model->getMedia('images');

        return MediaStream::create($model->name.'.zip')
            ->addMedia($modelFiles)
            ->addMedia($images);
    }

}
