<?php

namespace App\Jobs\ImportPipeline;

use DB;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\{
    ImportJob,
    ImportData,
    Media,
    Profile,
    Status,
};

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
        if(config('pixelfed.import.instagram.enabled') != true) {
            return;
        }

        $job = ImportJob::findOrFail($this->import->id);
        $profile = Profile::findOrFail($job->profile_id);
        $json = $job->mediaJson();
        $collection = $json['photos'];
        $files = $job->files;
        $monthHash = hash('sha1', date('Y').date('m'));
        $userHash = hash('sha1', $profile->id . (string) $profile->created_at);
        $fs = new Filesystem;

        foreach($collection as $import)
        {
            $caption = $import['caption'];
            try {
                $min = Carbon::create(2010, 10, 6, 0, 0, 0);
                $taken_at = Carbon::parse($import['taken_at']);
                if(!$min->lt($taken_at)) {
                    $taken_at = Carbon::now();
                }
            } catch (Exception $e) {
                
            }
            $filename = last( explode('/', $import['path']) );
            $importData = ImportData::whereJobId($job->id)
                ->whereOriginalName($filename)
                ->first();

            if(empty($importData) || is_file(storage_path("app/$importData->path")) == false) {
                continue;
            }

            DB::transaction(function() use(
                $fs, $job, $profile, $caption, $taken_at, $filename,
                $monthHash, $userHash, $importData
            ) {
                $status = new Status();
                $status->profile_id = $profile->id;
                $status->caption = strip_tags($caption);
                $status->is_nsfw = false;
                $status->type = 'photo';
                $status->scope = 'unlisted';
                $status->visibility = 'unlisted';
                $status->created_at = $taken_at;
                $status->save();


                $path = storage_path("app/$importData->path");
                $storagePath = "public/m/{$monthHash}/{$userHash}";
                $newPath = "app/$storagePath/$filename";
                $fs->move($path, storage_path($newPath));
                $path = $newPath;
                $hash = \hash_file('sha256', storage_path($path));
                $media = new Media();
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
                NewStatusPipeline::dispatch($status);
            });
        }

        $job->completed_at = Carbon::now();
        $job->save();
    }
}
