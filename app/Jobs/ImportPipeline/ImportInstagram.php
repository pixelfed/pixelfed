<?php

namespace App\Jobs\ImportPipeline;

use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Models\ImportData;
use App\Models\ImportJob;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportInstagram implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $import;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ImportJob $import)
    {
        $this->import = $import;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (config_cache('pixelfed.import.instagram.enabled') != true) {
            return;
        }

        $job = ImportJob::find($this->import->id);
        if (! $job) {
            return;
        }

        // The profile may have been soft-deleted (e.g. account deletion) after
        // this job was queued. findOrFail would throw inside handle() and the
        // job would retry until it lands in failed_jobs; drop it cleanly instead.
        $profile = Profile::find($job->profile_id);
        if (! $profile) {
            $job->delete();

            return;
        }

        $user = $profile->user;
        if (! $user) {
            $job->delete();

            return;
        }
        $json = $job->mediaJson();
        $collection = array_reverse($json['photos']);
        $files = $job->files;
        $monthHash = hash('sha1', date('Y').date('m'));
        $userHash = hash('sha1', $user->id.(string) $user->created_at);
        $fs = new Filesystem;

        foreach ($collection as $import) {
            $caption = $import['caption'];
            $taken_at = now();
            try {
                $min = Carbon::create(2010, 10, 6, 0, 0, 0);
                $taken_at = Carbon::parse($import['taken_at']);
                if (! $min->lt($taken_at)) {
                    $taken_at = now();
                }
            } catch (\Exception) {

            }
            $filename = last(explode('/', $import['path']));
            $importData = ImportData::whereJobId($job->id)
                ->whereOriginalName($filename)
                ->first();

            if (empty($importData)) {
                continue;
            }

            $originalPath = storage_path("app/$importData->path");

            if (is_file($originalPath) === false) {
                Log::warning('ImportInstagram: source file missing, skipping', [
                    'job_id' => $job->id,
                    'original_name' => $filename,
                    'path' => $importData->path,
                ]);

                continue;
            }

            DB::transaction(function () use (
                $fs, $profile, $caption, $taken_at, $filename,
                $monthHash, $userHash, $importData
            ) {
                $status = new Status;
                $status->profile_id = $profile->id;
                $status->caption = strip_tags($caption);
                $status->rendered = '';
                $status->is_nsfw = false;
                $status->type = 'photo';
                $status->scope = 'unlisted';
                $status->visibility = 'unlisted';
                $status->created_at = $taken_at;
                $status->save();

                $path = storage_path("app/$importData->path");
                $storagePath = "public/m/{$monthHash}/{$userHash}";
                $dir = "app/$storagePath";
                if (! is_dir(storage_path($dir))) {
                    mkdir(storage_path($dir), 0755, true);
                }
                $newPath = "$dir/$filename";
                // Copy, not move: the source must survive until the DB
                // transaction commits so a rolled-back attempt stays retryable.
                $fs->copy($path, storage_path($newPath));
                $path = $newPath;
                $hash = \hash_file('sha256', storage_path($path));
                $media = new Media;
                $media->status_id = $status->id;
                $media->profile_id = $profile->id;
                $media->user_id = $profile->user->id;
                $media->media_path = "$storagePath/$filename";
                $media->original_sha256 = $hash;
                $media->size = $fs->size(storage_path($path));
                $media->mime = $fs->mimeType(storage_path($path));
                $media->filter_class = null;
                $media->filter_name = null;
                $media->order = 1;
                $media->save();
                ImageOptimize::dispatch($media);
            });

            // Commit succeeded, so the original source is no longer the only
            // copy and can be removed.
            $fs->delete($originalPath);
        }

        $job->completed_at = now();
        $job->save();
    }
}
