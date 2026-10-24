<?php

namespace App\Jobs\VideoPipeline;

use App\Services\MediaService;
use App\Services\StatusService;
use FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoHlsPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

    public $timeout = 900;

    public $tries = 3;

    public $maxExceptions = 1;

    public $failOnTimeout = true;

    public $deleteWhenMissingModels = true;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'media:video-hls:id-'.$this->media->id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("media:video-hls:id-{$this->media->id}"))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($media)
    {
        $this->media = $media;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $depCheck = Cache::rememberForever('video-pipeline:hls:depcheck', function () {
            $bin = config('laravel-ffmpeg.ffmpeg.binaries');
            $output = shell_exec($bin.' -version');
            if ($output && preg_match('/ffmpeg version ([^\s]+)/', $output, $matches)) {
                $version = $matches[1];

                return (version_compare($version, config('laravel-ffmpeg.min_hls_version')) >= 0) ? 'ok' : false;
            }

            return false;
        });

        if (! $depCheck || $depCheck !== 'ok') {
            return;
        }

        $media = $this->media;

        $bitrate = (new X264)->setKiloBitrate(config('media.hls.bitrate') ?? 1000);

        $mp4 = $media->media_path;
        $man = str_replace('.mp4', '.m3u8', $mp4);

        // On cloud-storage instances the local mp4 may already have been
        // uploaded and unlinked before this job runs (storage pipeline and
        // HLS run on separate queues). Pull the source back down from the
        // cloud disk when that happens so transcoding can still proceed, and
        // bail quietly if neither copy exists rather than failing for good
        // (maxExceptions is 1, so a throw here is permanent).
        $fetchedFromCloud = false;
        $local = Storage::disk('local');

        if (! $local->exists($mp4)) {
            $cloud = Storage::disk(config('filesystems.cloud'));

            if (! $cloud->exists($mp4)) {
                Log::warning('VideoHlsPipeline: source mp4 missing on local and cloud, skipping', [
                    'media_id' => $media->id,
                    'media_path' => $mp4,
                ]);

                return;
            }

            $stream = $cloud->readStream($mp4);
            $local->writeStream($mp4, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            $fetchedFromCloud = true;
        }

        try {
            FFMpeg::fromDisk('local')
                ->open($mp4)
                ->exportForHLS()
                ->setSegmentLength(16)
                ->setKeyFrameInterval(48)
                ->addFormat($bitrate)
                ->save($man);

            $media->hls_path = $man;
            $media->hls_transcoded_at = now();
            $media->save();

            MediaService::del($media->status_id);
            usleep(50000);
            StatusService::del($media->status_id);
        } finally {
            // Only remove the copy this job pulled down, never a file that was
            // already present locally.
            if ($fetchedFromCloud && $local->exists($mp4)) {
                $local->delete($mp4);
            }
        }
    }
}
