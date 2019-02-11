<?php

namespace App\Jobs\StatusPipeline;

use App\{
    Notification,
    Report,
    Status,
    StatusHashtag,
};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StatusDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        $this->unlinkRemoveMedia($status);
    }

    public function unlinkRemoveMedia($status)
    {
        foreach ($status->media as $media) {
            $thumbnail = storage_path("app/{$media->thumbnail_path}");
            $photo = storage_path("app/{$media->media_path}");

            try {
                if (is_file($thumbnail)) {
                    unlink($thumbnail);
                }
                if (is_file($photo)) {
                    unlink($photo);
                }
                $media->delete();
            } catch (Exception $e) {
            }
        }
        $comments = Status::where('in_reply_to_id', $status->id)->get();
        foreach ($comments as $comment) {
            $comment->in_reply_to_id = null;
            $comment->save();
            Notification::whereItemType('App\Status')
                ->whereItemId($comment->id)
                ->delete();
        }

        $status->likes()->delete();
        Notification::whereItemType('App\Status')
            ->whereItemId($status->id)
            ->delete();
        StatusHashtag::whereStatusId($status->id)->delete();
        Report::whereObjectType('App\Status')
            ->whereObjectId($status->id)
            ->delete();
        $status->delete();

        return true;
    }
}
