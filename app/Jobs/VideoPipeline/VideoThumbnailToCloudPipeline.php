<?php

namespace App\Jobs\VideoPipeline;

use App\Models\Media;
use App\Services\MediaService;
use App\Services\ResilientMediaStorageService;
use App\Services\StatusService;
use App\Util\Media\Blurhash;
use FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Timeout(900)]
#[Tries(3)]
#[MaxExceptions(1)]
#[FailOnTimeout]
#[DeleteWhenMissingModels]
#[UniqueFor(3600)]
class VideoThumbnailToCloudPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'media:video-thumb-to-cloud:id-'.$this->media->id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("media:video-thumb-to-cloud:id-{$this->media->id}"))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ((bool) config_cache('pixelfed.cloud_storage') === false) {
            return;
        }

        $media = $this->media;

        if ($media->mime != 'video/mp4') {
            return;
        }

        if ($media->profile_id === null || $media->status_id === null) {
            return;
        }

        if ($media->thumbnail_url) {
            return;
        }

        $base = $media->media_path;
        $path = explode('/', $base);
        $name = last($path);

        try {
            $t = explode('.', $name);
            $t = $t[0].'_thumb.jpeg';
            $i = count($path) - 1;
            $path[$i] = $t;
            $save = implode('/', $path);
            $video = FFMpeg::open($base)
                ->getFrameFromSeconds(1)
                ->export()
                ->toDisk('local')
                ->save($save);

            if (! $save) {
                return;
            }

            $media->thumbnail_path = $save;
            $p = explode('/', $media->media_path);
            array_pop($p);
            $pt = explode('/', $save);
            $thumbname = array_pop($pt);
            $storagePath = implode('/', $p);
            $thumb = storage_path('app/'.$save);
            $thumbUrl = ResilientMediaStorageService::store($storagePath, $thumb, $thumbname);
            $media->thumbnail_url = $thumbUrl;
            $media->save();

            $blurhash = Blurhash::generate($media);
            if ($blurhash) {
                $media->blurhash = $blurhash;
                $media->save();
            }

            if (str_starts_with($save, 'public/m/_v2/') && str_ends_with($save, '.jpeg')) {
                Storage::delete($save);
            }

            if (str_starts_with($media->media_path, 'public/m/_v2/') && str_ends_with($media->media_path, '.mp4')) {
                Storage::disk('local')->delete($media->media_path);
            }
        } catch (\Exception $e) {
        }

        if ($media->status_id) {
            Cache::forget('status:transformer:media:attachments:'.$media->status_id);
            MediaService::del($media->status_id);
            Cache::forget('status:thumb:nsfw0'.$media->status_id);
            Cache::forget('status:thumb:nsfw1'.$media->status_id);
            Cache::forget('pf:services:sh:id:'.$media->status_id);
            StatusService::del($media->status_id);
        }
    }
}
