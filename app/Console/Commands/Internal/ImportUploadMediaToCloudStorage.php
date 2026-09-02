<?php

namespace App\Console\Commands\Internal;

use App\Jobs\ImportPipeline\ImportMediaToCloudPipeline;
use App\Models\ImportPost;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\progress;

#[Signature('app:import-upload-media-to-cloud-storage {--limit=500}')]
#[Description('Migrate media imported from Instagram to S3 cloud storage.')]
class ImportUploadMediaToCloudStorage extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (
            (bool) config('import.instagram.storage.cloud.enabled') === false ||
            (bool) config_cache('pixelfed.cloud_storage') === false
        ) {
            $this->error('Aborted. Cloud storage is not enabled for IG imports.');

            return;
        }

        $limit = $this->option('limit');

        $progress = progress(label: 'Migrating import media', steps: $limit);

        $progress->start();

        $posts = ImportPost::whereUploadedToS3(false)->take($limit)->get();

        foreach ($posts as $post) {
            ImportMediaToCloudPipeline::dispatch($post)->onQueue('low');
            $progress->advance();
        }

        $progress->finish();
    }
}
