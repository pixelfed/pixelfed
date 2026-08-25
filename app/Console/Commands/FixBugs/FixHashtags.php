<?php

namespace App\Console\Commands\FixBugs;

use App\Hashtag;
use App\StatusHashtag;
use Illuminate\Console\Command;

class FixHashtags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:hashtags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Hashtags';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Running Fix Hashtags command');
        $this->info('Pixelfed version: '.config('pixelfed.version'));
        $this->line(' ');

        $this->fixBrokenSlugs();
        $this->deleteOrphanedHashtags();
        $this->fixMissingVisibility();

        $this->info('Done!');

        return Command::SUCCESS;
    }

    protected function fixBrokenSlugs()
    {
        $this->info('Checking for broken hashtag slugs...');

        $count = 0;
        foreach (Hashtag::lazyById(100, 'id') as $tag) {
            $slug = str_slug($tag->name, '-', false);
            if ($slug === $tag->slug) {
                continue;
            }
            $exists = Hashtag::whereName($tag->name)->where('slug', $slug)->where('id', '!=', $tag->id)->exists();
            if (! $exists) {
                continue;
            }
            $this->info("Broken: {$tag->slug} should be {$slug}");
            $tag->slug = $slug;
            $tag->save();
            $count++;
        }

        $this->info("Fixed {$count} broken tag slugs.");
        $this->line(' ');
    }

    protected function deleteOrphanedHashtags()
    {
        $missingCount = StatusHashtag::doesntHave('profile')->doesntHave('status')->count();

        if ($missingCount > 0) {
            $this->info("Found {$missingCount} orphaned StatusHashtag records to delete...");
            $bar = $this->output->createProgressBar($missingCount);
            $bar->start();

            StatusHashtag::doesntHave('profile')->doesntHave('status')->lazyById(100)->each(function ($tag) use ($bar) {
                $tag->delete();
                $bar->advance();
            });

            $bar->finish();
            $this->line(' ');
        } else {
            $this->info('No orphaned hashtags found.');
        }

        $this->line(' ');
    }

    protected function fixMissingVisibility()
    {
        $count = StatusHashtag::whereNull('status_visibility')->count();

        if ($count === 0) {
            $this->info('No hashtags with missing visibility to fix.');

            return;
        }

        $this->info("Found {$count} hashtags with missing status_visibility...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        StatusHashtag::with('status')
            ->whereNull('status_visibility')
            ->chunk(50, function ($tags) use ($bar) {
                foreach ($tags as $tag) {
                    if (! $tag->status || ! $tag->status->scope) {
                        $bar->advance();

                        continue;
                    }
                    $tag->status_visibility = $tag->status->scope;
                    $tag->save();
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->line(' ');
    }
}
