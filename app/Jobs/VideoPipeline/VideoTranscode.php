<?php

namespace App\Jobs\VideoPipeline;

use App\Media;
use App\Services\MediaService;
use App\Services\StatusService;
use Cache;
use FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Log;

class VideoTranscode implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const TRANSCODABLE_MIMES = ['video/quicktime', 'video/x-m4v'];

    protected $media;

    public $timeout = 1800;

    public $tries = 2;

    public $maxExceptions = 1;

    public $failOnTimeout = true;

    public $deleteWhenMissingModels = true;

    public $uniqueFor = 7200;

    public function uniqueId(): string
    {
        return 'media:video-transcode:id-'.$this->media->id;
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("media:video-transcode:id-{$this->media->id}"))->shared()->dontRelease()];
    }

    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    public function handle()
    {
        $media = $this->media;

        if ($media->mime === 'video/mp4') {
            VideoThumbnail::dispatch($media)->onQueue('mmo');

            return;
        }

        if (! in_array($media->mime, self::TRANSCODABLE_MIMES, true)) {
            return;
        }

        $sourcePath = $media->media_path;
        $info = pathinfo($sourcePath);
        $targetPath = ($info['dirname'] === '.' ? '' : $info['dirname'].'/').$info['filename'].'.mp4';

        if ($targetPath === $sourcePath) {
            $targetPath = ($info['dirname'] === '.' ? '' : $info['dirname'].'/').$info['filename'].'_pf.mp4';
        }

        try {
            $format = new X264('aac', 'libx264');
            $format->setAdditionalParameters(['-movflags', '+faststart']);

            FFMpeg::open($sourcePath)
                ->export()
                ->toDisk('local')
                ->inFormat($format)
                ->save($targetPath);

            $originalPath = $media->media_path;
            $media->media_path = $targetPath;
            $media->mime = 'video/mp4';

            if (Storage::disk('local')->exists($targetPath)) {
                $media->size = Storage::disk('local')->size($targetPath);
            }

            $media->save();

            try {
                if (Storage::disk('local')->exists($originalPath)) {
                    Storage::disk('local')->delete($originalPath);
                }
            } catch (\Exception $cleanupException) {
                if (config('app.dev_log')) {
                    Log::warning('Video transcode cleanup failed: '.$cleanupException->getMessage());
                }
            }

            if ($media->status_id) {
                Cache::forget('status:transformer:media:attachments:'.$media->status_id);
                MediaService::del($media->status_id);
                StatusService::del($media->status_id);
            }

            VideoThumbnail::dispatch($media)->onQueue('mmo');
        } catch (\Exception $e) {
            if (config('app.dev_log')) {
                Log::error('Video transcode failed for media '.$media->id.': '.$e->getMessage());
            }

            throw $e;
        }
    }
}
