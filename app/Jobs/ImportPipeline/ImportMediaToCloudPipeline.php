<?php

namespace App\Jobs\ImportPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use App\Models\ImportPost;
use App\Media;
use App\Services\MediaStorageService;
use Illuminate\Support\Facades\Storage;
use App\Jobs\VideoPipeline\VideoThumbnailToCloudPipeline;

class ImportMediaToCloudPipeline implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $importPost;

    public $timeout = 900;
    public $tries = 3;
    public $maxExceptions = 1;
    public $failOnTimeout = true;

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
        return 'import-media-to-cloud-pipeline:ip-id:' . $this->importPost->id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("import-media-to-cloud-pipeline:ip-id:{$this->importPost->id}"))->shared()->dontRelease()];
    }

    /**
    * Delete the job if its models no longer exist.
    *
    * @var bool
    */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(ImportPost $importPost)
    {
        $this->importPost = $importPost;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $ip = $this->importPost;

        if(
            $ip->status_id === null ||
            $ip->uploaded_to_s3 === true ||
            (bool) config_cache('pixelfed.cloud_storage') === false) {
            return;
        }

        $media = Media::whereStatusId($ip->status_id)->get();

        if(!$media || !$media->count()) {
            $importPost = ImportPost::find($ip->id);
            $importPost->uploaded_to_s3 = true;
            $importPost->save();
            return;
        }

        foreach($media as $mediaPart) {
            $this->handleMedia($mediaPart);
        }
    }

    protected function handleMedia($media)
    {
        $ip = $this->importPost;

        $importPost = ImportPost::find($ip->id);

        if(!$importPost) {
            return;
        }

        $res = MediaStorageService::move($media);

        $importPost->uploaded_to_s3 = true;
        $importPost->save();

        if(!$res) {
            return;
        }

        if($res === 'invalid file') {
            return;
        }

        if($res === 'success') {
            if($media->mime === 'video/mp4') {
                VideoThumbnailToCloudPipeline::dispatch($media)->onQueue('low');
            } else {
                Storage::disk('local')->delete($media->media_path);
            }
        }
    }
}
