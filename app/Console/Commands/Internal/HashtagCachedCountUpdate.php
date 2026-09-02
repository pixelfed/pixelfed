<?php

namespace App\Console\Commands\Internal;

use App\Models\Hashtag;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:hashtag-cached-count-update {--limit=100}')]
#[Description('Update cached counter of hashtags')]
class HashtagCachedCountUpdate extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $tags = Hashtag::whereNull('cached_count')->limit($limit)->get();
        $count = count($tags);
        if (! $count) {
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($tags as $tag) {
            $count = DB::table('status_hashtags')->whereHashtagId($tag->id)->count();
            if (! $count) {
                $tag->cached_count = 0;
                $tag->saveQuietly();
                $bar->advance();

                continue;
            }
            $tag->cached_count = $count;
            $tag->saveQuietly();
            $bar->advance();
        }
        $bar->finish();
        $this->line(' ');

    }
}
