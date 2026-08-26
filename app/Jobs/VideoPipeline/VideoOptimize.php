<?php

namespace App\Jobs\VideoPipeline;

use App\Media;
use App\Services\StatusService;
use FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class VideoOptimize implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $media = $this->media;

        if ($media->mime != 'video/mp4') {
            return;
        }

        if ($media->processed_at != null) {
            return;
        }

        $this->transcode($media);
    }

    protected function transcode($media)
    {
        return;
        $base = $media->media_path;
        $path = explode('/', $base);
        $name = last($path);

        $video = FFMpeg::open($base);

        $t = explode('.', $name);
        $t = $t[0].'_op1k.webm';
        $i = count($path) - 1;
        $path[$i] = $t;
        $saveWebm = implode('/', $path);

        $video->export()
            ->toDisk('local')
            ->inFormat((new FFMpeg\Format\Video\WebM))
            ->save($saveWebm);

        $media->optimized_url = Storage::url($saveWebm);
        $media->save();

        if ($media->status_id) {
            StatusService::del($media->status_id);
        }

        return 1;
    }
}
