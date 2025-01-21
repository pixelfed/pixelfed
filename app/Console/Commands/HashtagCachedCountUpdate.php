<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Hashtag;
use App\StatusHashtag;
use DB;

class HashtagCachedCountUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hashtag-cached-count-update {--limit=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update cached counter of hashtags';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $tags = Hashtag::whereNull('cached_count')->limit($limit)->get();
        $count = count($tags);
        if(!$count) {
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach($tags as $tag) {
            $count = DB::table('status_hashtags')->whereHashtagId($tag->id)->count();
            if(!$count) {
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
        return;
    }
}
