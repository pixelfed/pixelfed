<?php

namespace App\Console\Commands;

use App\Media;
use App\Services\MediaService;
use App\Services\StatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MediaReplaceDomainCommand extends Command
{
    protected $signature = 'media:replacedomain {--original= : Original domain to replace} {--new= : New domain to use}';

    protected $description = 'Replace CDN domain in media URLs and clear associated caches';

    public function handle()
    {
        $originalDomain = $this->option('original');
        $newDomain = $this->option('new');

        if (! $originalDomain || ! $newDomain) {
            $this->error('Both --original and --new options are required');

            return 1;
        }

        if (! str_starts_with($originalDomain, 'https://')) {
            $this->error('Original domain must start with https://');

            return 1;
        }

        if (! str_starts_with($newDomain, 'https://')) {
            $this->error('New domain must start with https://');

            return 1;
        }

        $originalDomain = rtrim($originalDomain, '/');
        $newDomain = rtrim($newDomain, '/');

        if (preg_match('/[^a-zA-Z0-9\-\._\/:]/', $originalDomain) ||
            preg_match('/[^a-zA-Z0-9\-\._\/:]/', $newDomain)) {
            $this->error('Domains contain invalid characters');

            return 1;
        }

        $sampleMedia = Media::where('cdn_url', 'LIKE', $originalDomain.'%')->first();

        if (! $sampleMedia) {
            $this->error('No media entries found with the specified domain.');

            return 1;
        }

        $sampleNewUrl = str_replace($originalDomain, $newDomain, $sampleMedia->cdn_url);

        $this->info('Please verify this URL transformation:');
        $this->newLine();
        $this->info('Original URL:');
        $this->line($sampleMedia->cdn_url);
        $this->info('Will be changed to:');
        $this->line($sampleNewUrl);
        $this->newLine();
        $this->info('Please verify in your browser that both URLs are accessible.');

        if (! $this->confirm('Do you want to proceed with the replacement?')) {
            $this->info('Operation cancelled.');

            return 0;
        }

        $query = Media::where('cdn_url', 'LIKE', $originalDomain.'%');
        $count = $query->count();

        $this->info("Found {$count} media entries to update.");

        $bar = $this->output->createProgressBar($count);
        $errors = [];

        $query->chunkById(1000, function ($medias) use ($originalDomain, $newDomain, $bar, &$errors) {
            foreach ($medias as $media) {
                try {
                    if (! str_starts_with($media->cdn_url, 'https://')) {
                        $errors[] = "Media ID {$media->id} has invalid URL format: {$media->cdn_url}";
                        $bar->advance();

                        continue;
                    }

                    DB::transaction(function () use ($media, $originalDomain, $newDomain) {
                        $media->cdn_url = str_replace($originalDomain, $newDomain, $media->cdn_url);
                        $media->save();

                        if ($media->status_id) {
                            MediaService::del($media->status_id);
                            StatusService::del($media->status_id);
                        }
                    });
                    $bar->advance();
                } catch (\Exception $e) {
                    $errors[] = "Failed to update Media ID {$media->id}: {$e->getMessage()}";
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine();

        if (! empty($errors)) {
            $this->newLine();
            $this->warn('Completed with errors:');
            foreach ($errors as $error) {
                $this->error($error);
            }

            return 1;
        }

        $this->info('Domain replacement completed successfully.');

        return 0;
    }
}
