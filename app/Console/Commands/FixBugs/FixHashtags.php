<?php

namespace App\Console\Commands\FixBugs;

use App\Models\Hashtag;
use App\Models\StatusHashtag;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('fix:hashtags')]
#[Description('Fix Hashtags')]
class FixHashtags extends Command
{
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $this->info('       ____  _           ______         __  ');
        $this->info('      / __ \(_)  _____  / / __/__  ____/ /  ');
        $this->info('     / /_/ / / |/_/ _ \/ / /_/ _ \/ __  /   ');
        $this->info('    / ____/ />  </  __/ / __/  __/ /_/ /    ');
        $this->info('   /_/   /_/_/|_|\___/_/_/  \___/\__,_/     ');
        $this->info(' ');
        $this->info(' ');
        $this->info('Pixelfed version: '.config('pixelfed.version'));
        $this->info(' ');
        $this->info('Running Fix Hashtags command');
        $this->info(' ');

        $this->info('Found '.Hashtag::count().' total hashtags!');
        $count = 0;
        foreach (Hashtag::lazyById(100, 'id') as $tag) {
            $slug = Str::slug($tag->name, '-', false);
            if ($slug === $tag->slug) {
                continue;
            }
            $count = Hashtag::whereName($tag->name)->where('slug', '===', $slug)->count();
            if (! $count) {
                continue;
            }
            $this->info($count.':'.$tag->slug.' : '.Str::slug($tag->name, '-', false));

        }

        $this->info('Found '.$count.' broken tags');

        return;

        $missingCount = StatusHashtag::doesntHave('profile')->doesntHave('status')->count();
        if ($missingCount > 0) {
            $this->info("Found {$missingCount} orphaned StatusHashtag records to delete ...");
            $this->info(' ');
            $bar = $this->output->createProgressBar($missingCount);
            $bar->start();
            foreach (StatusHashtag::doesntHave('profile')->doesntHave('status')->get() as $tag) {
                $tag->delete();
                $bar->advance();
            }
            $bar->finish();
            $this->info(' ');
        } else {
            $this->info(' ');
            $this->info('Found no orphaned hashtags to delete!');
        }

        $this->info(' ');

        $count = StatusHashtag::whereNull('status_visibility')->count();
        if ($count > 0) {
            $this->info("Found {$count} hashtags to fix ...");
            $this->info(' ');
        } else {
            $this->info('Found no hashtags to fix!');
            $this->info(' ');

            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        StatusHashtag::with('status')
            ->whereNull('status_visibility')
            ->chunk(50, function ($tags) use ($bar) {
                foreach ($tags as $tag) {
                    if (! $tag->status || ! $tag->status->scope) {
                        continue;
                    }
                    $tag->status_visibility = $tag->status->scope;
                    $tag->save();
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->info(' ');
        $this->info(' ');
    }
}
