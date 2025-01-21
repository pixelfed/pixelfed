<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImportPost;
use App\Jobs\ImportPipeline\ImportMediaToCloudPipeline;
use function Laravel\Prompts\progress;

class ImportUploadMediaToCloudStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-upload-media-to-cloud-storage {--limit=500}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate media imported from Instagram to S3 cloud storage.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if(
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

        foreach($posts as $post) {
            ImportMediaToCloudPipeline::dispatch($post)->onQueue('low');
            $progress->advance();
        }

        $progress->finish();
    }
}
