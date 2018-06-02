<?php

namespace App\Jobs\StatusPipeline;

use Cache;
use App\{
    Hashtag,
    Media,
    Status,
    StatusHashtag
};
use App\Util\Lexer\Hashtag as HashtagLexer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class StatusEntityLexer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Status $status)
    {
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status = $this->status;
        $this->parseHashtags();
    }

    public function parseHashtags()
    {
        $status = $this->status;
        $text = e($status->caption);
        $tags = HashtagLexer::getHashtags($text);
        $rendered = $text;
        if(count($tags) > 0) {
            $rendered = HashtagLexer::replaceHashtagsWithLinks($text);
        }
        $status->rendered = $rendered;
        $status->save();
        
        Cache::forever('post.' . $status->id, $status);

        foreach($tags as $tag) {
            $slug = str_slug($tag);
            
            $htag = Hashtag::firstOrCreate(
                ['name' => $tag],
                ['slug' => $slug]
            );

            $stag = new StatusHashtag;
            $stag->status_id = $status->id;
            $stag->hashtag_id = $htag->id;
            $stag->save();
        }

    }
}
