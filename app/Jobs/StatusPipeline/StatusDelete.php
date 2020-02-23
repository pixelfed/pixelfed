<?php

namespace App\Jobs\StatusPipeline;

use DB;
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
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use App\Transformer\ActivityPub\Verb\DeleteNote;
use App\Util\ActivityPub\Helpers;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use App\Util\ActivityPub\HttpSignature;

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

        if(config('federation.activitypub.enabled') == true) {
            $this->fanoutDelete($status);
        } else {
            $this->unlinkRemoveMedia($status);
        }

    }

    public function unlinkRemoveMedia(Status $status): bool
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
        if($status->in_reply_to_id) {
            DB::transaction(function() use($status) {
                $parent = Status::findOrFail($status->in_reply_to_id);
                --$parent->reply_count;
                $parent->save();
            });
        }
        DB::transaction(function() use($status) {
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
            $status->forceDelete();
        });

        return true;
    }

    /**
     * @return void
     */
    protected function fanoutDelete(Status $status)
    {
        $audience = $status->profile->getAudienceInbox();
        $profile = $status->profile;

        $fractal = new Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Fractal\Resource\Item($status, new DeleteNote());
        $activity = $fractal->createData($resource)->toArray();

        $this->unlinkRemoveMedia($status);
        
        $payload = json_encode($activity);
        
        $client = new Client([
            'timeout'  => config('federation.activitypub.delivery.timeout')
        ]);

        $requests = function($audience) use ($client, $activity, $profile, $payload) {
            foreach($audience as $url) {
                $headers = HttpSignature::sign($profile, $url, $activity);
                yield function() use ($client, $url, $headers, $payload) {
                    return $client->postAsync($url, [
                        'curl' => [
                            CURLOPT_HTTPHEADER => $headers, 
                            CURLOPT_POSTFIELDS => $payload,
                            CURLOPT_HEADER => true
                        ]
                    ]);
                };
            }
        };

        $pool = new Pool($client, $requests($audience), [
            'concurrency' => config('federation.activitypub.delivery.concurrency'),
            'fulfilled' => function ($response, $index) {
            },
            'rejected' => function ($reason, $index) {
            }
        ]);
        
        $promise = $pool->promise();

        $promise->wait();

    }
}
