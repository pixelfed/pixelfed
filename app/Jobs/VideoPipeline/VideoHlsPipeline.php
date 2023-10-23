<?php

namespace App\Jobs\VideoPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use FFMpeg\Format\Video\X264;
use FFMpeg;
use Cache;
use App\Services\MediaService;
use App\Services\StatusService;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

class VideoHlsPipeline implements ShouldQueue, ShouldBeUniqueUntilProcessing
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
        return 'media:video-hls:id-' . $this->media->id;
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
        $depCheck = Cache::rememberForever('video-pipeline:hls:depcheck', function() {
            $bin = config('laravel-ffmpeg.ffmpeg.binaries');
            $output = shell_exec($bin . ' -version');
            if($output && preg_match('/ffmpeg version ([^\s]+)/', $output, $matches)) {
                $version = $matches[1];
                return (version_compare($version, config('laravel-ffmpeg.min_hls_version')) >= 0) ? 'ok' : false;
            } else {
                return false;
            }
        });

        if(!$depCheck || $depCheck !== 'ok') {
            return;
        }

        $media = $this->media;

        $bitrate = (new X264)->setKiloBitrate(config('media.hls.bitrate') ?? 1000);

        $mp4 = $media->media_path;
        $man = str_replace('.mp4', '.m3u8', $mp4);

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

        return;
    }
}
