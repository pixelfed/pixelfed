<?php

namespace App\Jobs\ImportPipeline;

use App\Jobs\VideoPipeline\VideoThumbnailToCloudPipeline;
use App\Models\ImportPost;
use App\Models\Media;
use App\Services\MediaStorageService;
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
use Illuminate\Support\Facades\Storage;

#[Timeout(900)]
#[Tries(3)]
#[MaxExceptions(1)]
#[FailOnTimeout]
#[UniqueFor(3600)]
#[DeleteWhenMissingModels]
class ImportMediaToCloudPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $importPost;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'import-media-to-cloud-pipeline:ip-id:'.$this->importPost->id;
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

        if (
            $ip->status_id === null ||
            $ip->uploaded_to_s3 === true ||
            (bool) config_cache('pixelfed.cloud_storage') === false) {
            return;
        }

        $media = Media::whereStatusId($ip->status_id)->get();

        if (! $media || ! $media->count()) {
            $importPost = ImportPost::find($ip->id);
            $importPost->uploaded_to_s3 = true;
            $importPost->save();

            return;
        }

        $allSucceeded = true;

        foreach ($media as $mediaPart) {
            if (! $this->handleMedia($mediaPart)) {
                $allSucceeded = false;
            }
        }

        if ($allSucceeded) {
            $importPost = ImportPost::find($ip->id);
            if ($importPost) {
                $importPost->uploaded_to_s3 = true;
                $importPost->save();
            }
        }
    }

    protected function handleMedia($media)
    {
        // Skip media already uploaded to cloud storage
        if ($media->cdn_url) {
            return true;
        }

        $ip = $this->importPost;

        $importPost = ImportPost::find($ip->id);

        if (! $importPost) {
            return false;
        }

        $res = MediaStorageService::move($media);

        if (! $res) {
            return false;
        }

        if ($res === 'invalid file') {
            return false;
        }

        if ($res === 'success') {
            if ($media->mime === 'video/mp4') {
                VideoThumbnailToCloudPipeline::dispatch($media)->onQueue('low');
            } else {
                Storage::disk('local')->delete($media->media_path);
            }

            return true;
        }

        return false;
    }
}
