<?php

namespace App\Jobs\StatusPipeline;

use Cache;
use App\{
    Hashtag,
    Media,
    Mention,
    Profile,
    Status,
    StatusHashtag
};
use App\Util\Lexer\Hashtag as HashtagLexer;
use App\Util\Lexer\{Autolink, Extractor};
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\MentionPipeline\MentionPipeline;

class StatusEntityLexer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;
    protected $entities;
    protected $autolink;

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
        $this->parseEntities();
    }

    public function parseEntities()
    {
        $this->extractEntities();
    }

    public function extractEntities()
    {
        $this->entities = Extractor::create()->extract($this->status->caption);
        $this->autolinkStatus();    
    }

    public function autolinkStatus()
    {
        $this->autolink = Autolink::create()->autolink($this->status->caption);
        $this->storeEntities();
    }

    public function storeEntities()
    {
        $status = $this->status;
        $this->storeHashtags();
        $this->storeMentions();
        $status->rendered = $this->autolink;
        $status->entities = json_encode($this->entities);
        $status->save();
    }

    public function storeHashtags()
    {
        $tags = array_unique($this->entities['hashtags']);
        $status = $this->status;

        foreach($tags as $tag) {
            $slug = str_slug($tag);
            
            $htag = Hashtag::firstOrCreate(
                ['name' => $tag],
                ['slug' => $slug]
            );

            StatusHashtag::firstOrCreate(
                ['status_id' => $status->id],
                ['hashtag_id' => $htag->id]
            );
        }
    }

    public function storeMentions()
    {
        $mentions = array_unique($this->entities['mentions']);
        $status = $this->status;

        foreach($mentions as $mention) {
            $mentioned = Profile::whereUsername($mention)->first();
            
            if(empty($mentioned) || !isset($mentioned->id)) {
                continue;
            }

            $m = new Mention;
            $m->status_id = $status->id;
            $m->profile_id = $mentioned->id;
            $m->save();

            MentionPipeline::dispatch($status, $m);
        }
    }

}
