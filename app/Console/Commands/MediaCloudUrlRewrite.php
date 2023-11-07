<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Media;
use Cache, Storage;
use Illuminate\Contracts\Console\PromptsForMissingInput;

class MediaCloudUrlRewrite extends Command implements PromptsForMissingInput
{
    /**
    * The name and signature of the console command.
    *
    * @var string
    */
    protected $signature = 'media:cloud-url-rewrite {oldDomain} {newDomain}';

    /**
    * Prompt for missing input arguments using the returned questions.
    *
    * @return array
    */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'oldDomain' => 'The old S3 domain',
            'newDomain' => 'The new S3 domain'
        ];
    }
    /**
    * The console command description.
    *
    * @var string
    */
    protected $description = 'Rewrite S3 media urls from local users';

    /**
    * Execute the console command.
    */
    public function handle()
    {
        $this->preflightCheck();
        $this->bootMessage();
        $this->confirmCloudUrl();
    }

    protected function preflightCheck()
    {
        if(config_cache('pixelfed.cloud_storage') != true) {
            $this->info('Error: Cloud storage is not enabled!');
            $this->error('Aborting...');
            exit;
        }
    }

    protected function bootMessage()
    {
        $this->info('       ____  _           ______         __  ');
        $this->info('      / __ \(_)  _____  / / __/__  ____/ /  ');
        $this->info('     / /_/ / / |/_/ _ \/ / /_/ _ \/ __  /   ');
        $this->info('    / ____/ />  </  __/ / __/  __/ /_/ /    ');
        $this->info('   /_/   /_/_/|_|\___/_/_/  \___/\__,_/     ');
        $this->info(' ');
        $this->info('    Media Cloud Url Rewrite Tool');
        $this->info('    ===');
        $this->info('    Old S3: ' . trim($this->argument('oldDomain')));
        $this->info('    New S3: ' . trim($this->argument('newDomain')));
        $this->info(' ');
    }

    protected function confirmCloudUrl()
    {
        $disk = Storage::disk(config('filesystems.cloud'))->url('test');
        $domain = parse_url($disk, PHP_URL_HOST);
        if(trim($this->argument('newDomain')) !== $domain) {
            $this->error('Error: The new S3 domain you entered is not currently configured');
            exit;
        }

        if(!$this->confirm('Confirm this is correct')) {
            $this->error('Aborting...');
            exit;
        }

        $this->updateUrls();
    }

    protected function updateUrls()
    {
        $this->info('Updating urls...');
        $oldDomain = trim($this->argument('oldDomain'));
        $newDomain = trim($this->argument('newDomain'));
        $disk = Storage::disk(config('filesystems.cloud'));
        $count = Media::whereNotNull('cdn_url')->count();
        $bar = $this->output->createProgressBar($count);
        $counter = 0;
        $bar->start();
        foreach(Media::whereNotNull('cdn_url')->lazyById(1000, 'id') as $media) {
            if(strncmp($media->media_path, 'http', 4) === 0) {
                $bar->advance();
                continue;
            }
            $cdnHost = parse_url($media->cdn_url, PHP_URL_HOST);
            if($oldDomain != $cdnHost || $newDomain == $cdnHost) {
                $bar->advance();
                continue;
            }

            $media->cdn_url = str_replace($oldDomain, $newDomain, $media->cdn_url);

            if($media->thumbnail_url != null) {
                $thumbHost = parse_url($media->thumbnail_url, PHP_URL_HOST);
                if($thumbHost == $oldDomain) {
                    $thumbUrl = $disk->url($media->thumbnail_path);
                    $media->thumbnail_url = $thumbUrl;
                }
            }

            if($media->optimized_url != null) {
                $optiHost = parse_url($media->optimized_url, PHP_URL_HOST);
                if($optiHost == $oldDomain) {
                    $optiUrl = str_replace($oldDomain, $newDomain, $media->optimized_url);
                    $media->optimized_url = $optiUrl;
                }
            }

            $media->save();
            $counter++;
            $bar->advance();
        }

        $bar->finish();

        $this->line(' ');
        $this->info('Finished! Updated ' . $counter . ' total records!');
        $this->line(' ');
        $this->info('Tip: Run `php artisan cache:clear` to purge cached urls');
    }
}
