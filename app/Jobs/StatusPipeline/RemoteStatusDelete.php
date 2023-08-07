<?php

namespace App\Jobs\StatusPipeline;

use DB, Cache, Storage;
use App\{
    AccountInterstitial,
    Bookmark,
    CollectionItem,
    DirectMessage,
    Like,
    Media,
    MediaTag,
    Mention,
    Notification,
    Report,
    Status,
    StatusArchived,
    StatusHashtag,
    StatusView
};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Fractal;
use Illuminate\Support\Str;
use League\Fractal\Serializer\ArraySerializer;
use App\Transformer\ActivityPub\Verb\DeleteNote;
use App\Util\ActivityPub\Helpers;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use App\Util\ActivityPub\HttpSignature;
use App\Services\AccountService;
use App\Services\CollectionService;
use App\Services\StatusService;
use App\Jobs\MediaPipeline\MediaDeletePipeline;

class RemoteStatusDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    public $timeout = 90;
    public $tries = 2;
    public $maxExceptions = 1;

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

        if($status->deleted_at) {
            return;
        }
        $profile = $this->status->profile;

        StatusService::del($status->id, true);

        if($profile->status_count && $profile->status_count > 0) {
            $profile->status_count = $profile->status_count - 1;
            $profile->save();
        }

        return $this->unlinkRemoveMedia($status);
    }

    public function unlinkRemoveMedia($status)
    {

        if($status->in_reply_to_id) {
            $parent = Status::find($status->in_reply_to_id);
            if($parent) {
                --$parent->reply_count;
                $parent->save();
                StatusService::del($parent->id);
            }
        }

        AccountInterstitial::where('item_type', 'App\Status')
            ->where('item_id', $status->id)
            ->delete();
        Bookmark::whereStatusId($status->id)->delete();
        CollectionItem::whereObjectType('App\Status')
            ->whereObjectId($status->id)
            ->get()
            ->each(function($col) {
                CollectionService::removeItem($col->collection_id, $col->object_id);
                $col->delete();
        });
        DirectMessage::whereStatusId($status->id)->delete();
        Like::whereStatusId($status->id)->forceDelete();
        Media::whereStatusId($status->id)
        ->get()
        ->each(function($media) {
            MediaDeletePipeline::dispatch($media)->onQueue('mmo');
        });
        MediaTag::where('status_id', $status->id)->delete();
        Mention::whereStatusId($status->id)->forceDelete();
        Notification::whereItemType('App\Status')
            ->whereItemId($status->id)
            ->forceDelete();
        Report::whereObjectType('App\Status')
            ->whereObjectId($status->id)
            ->delete();
        StatusArchived::whereStatusId($status->id)->delete();
        StatusHashtag::whereStatusId($status->id)->delete();
        StatusView::whereStatusId($status->id)->delete();
        Status::whereInReplyToId($status->id)->update(['in_reply_to_id' => null]);

        $status->delete();

        StatusService::del($status->id, true);
        AccountService::del($status->profile_id);

        return 1;
    }
}
