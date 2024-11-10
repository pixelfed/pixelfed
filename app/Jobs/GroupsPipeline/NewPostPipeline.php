<?php

namespace App\Jobs\GroupsPipeline;

use App\Util\Media\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Group;
use App\Models\GroupPost;
use App\Models\GroupHashtag;
use App\Models\GroupPostHashtag;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\Extractor;
use DB;

class NewPostPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;
    protected $entities;
    protected $autolink;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GroupPost $status)
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
        $profile = $this->status->profile;
        $status = $this->status;

        if ($profile->no_autolink == false) {
            $this->parseEntities();
        }
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
        $this->storeHashtags();
    }

    public function storeHashtags()
    {
        $tags = array_unique($this->entities['hashtags']);
        $status = $this->status;

        foreach ($tags as $tag) {
            if (mb_strlen($tag) > 124) {
                continue;
            }
            DB::transaction(function () use ($status, $tag) {
                $hashtag = GroupHashtag::firstOrCreate([
                    'name' => $tag,
                ]);

                GroupPostHashtag::firstOrCreate(
                    [
                        'status_id' => $status->id,
                        'group_id' => $status->group_id,
                        'hashtag_id' => $hashtag->id,
                        'profile_id' => $status->profile_id,
                        'status_visibility' => $status->visibility,
                    ]
                );
            });
        }
        $this->storeMentions();
    }

    public function storeMentions()
    {
        // todo
    }
}
