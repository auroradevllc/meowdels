<?php

namespace App\Jobs;

use App\Models\PrintModel;
use App\Support\ThreeMFConverter;
use File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Convert3MFModel implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PrintModel $model,
        public Media $media,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ThreeMFConverter $converter): void
    {
        $input = $this->media->getPath();

        $output = tempnam(sys_get_temp_dir(), 'conversion');

        try {
            $converter->convert($input, $output);

            $outputName = preg_replace('/\.3mf/', '.stl', pathinfo($output, PATHINFO_FILENAME));

            $this->model->addMedia($output)
                ->usingFileName($outputName);
        } finally {
            File::delete($output);
        }
    }
}
