<?php

namespace App\Jobs\GroupsPipeline;

use App\Util\Media\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Group;
use App\Models\GroupComment;
use App\Models\GroupPost;
use App\Models\GroupHashtag;
use App\Models\GroupPostHashtag;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\Extractor;
use DB;

class DeleteCommentPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $parent;
    protected $status;

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
    public function __construct($parent, $status)
    {
        $this->parent = $parent;
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $parent = $this->parent;
        $parent->reply_count = GroupComment::whereStatusId($parent->id)->count();
        $parent->save();

        return;
    }
}
