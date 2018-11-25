<?php

namespace App\Util\ActivityPub;

use Cache, DB, Log, Redis, Validator;
use App\{
    Activity,
    Follower,
    FollowRequest,
    Like,
    Notification,
    Profile,
    Status
};
use Carbon\Carbon;
use App\Util\ActivityPub\Helpers;
use App\Jobs\LikePipeline\LikePipeline;

class Inbox
{
    protected $headers;
    protected $profile;
    protected $payload;
    protected $logger;

    public function __construct($headers, $profile, $payload)
    {
        $this->headers = $headers;
        $this->profile = $profile;
        $this->payload = $payload;
    }

    public function handle()
    {
        $this->authenticatePayload();
    }

    public function authenticatePayload()
    {
        try {
           $signature = Helpers::validateSignature($this->headers, $this->payload);
           $payload = Helpers::validateObject($this->payload);
           if($signature == false) {
            return;
           }
        } catch (Exception $e) {
           return; 
        }
        $this->payloadLogger(); 
    }

    public function payloadLogger()
    {
        $logger = new Activity;
        $logger->data = json_encode($this->payload);
        $logger->save();
        $this->logger = $logger;
        Log::info('AP:inbox:activity:new:'.$this->logger->id);
        $this->handleVerb();
    }

    public function handleVerb()
    {
        $verb = $this->payload['type'];
        switch ($verb) {
            case 'Create':
                $this->handleCreateActivity();
                break;

            case 'Follow':
                $this->handleFollowActivity();
                break;

            case 'Announce':
                $this->handleAnnounceActivity();
                break;

            case 'Accept':
                $this->handleAcceptActivity();
                break;

            case 'Delete':
                $this->handleDeleteActivity();
                break;

            case 'Like':
                $this->handleLikeActivity();
                break;

            case 'Reject':
                $this->handleRejectActivity();
                break;

            case 'Undo':
                $this->handleUndoActivity();
                break;

            default:
                // TODO: decide how to handle invalid verbs.
                break;
        }
    }

    public function verifyNoteAttachment()
    {
        $activity = $this->payload['object'];

        if(isset($activity['inReplyTo']) && 
            !empty($activity['inReplyTo']) && 
            Helpers::validateUrl($activity['inReplyTo'])
        ) {
            // reply detected, skip attachment check
            return true;
        }

        $valid = Helpers::verifyAttachments($activity);

        return $valid;
    }

    public function actorFirstOrCreate($actorUrl)
    {
        return Helpers::profileFirstOrNew($actorUrl);
    }

    public function handleCreateActivity()
    {
        $activity = $this->payload['object'];
        if(!$this->verifyNoteAttachment()) {
            return;
        }
        if($activity['type'] == 'Note' && !empty($activity['inReplyTo'])) {
            $this->handleNoteReply();

        } elseif($activity['type'] == 'Note' && !empty($activity['attachment'])) {
            $this->handleNoteCreate();
        }
    }

    public function handleNoteReply()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        $inReplyTo = $activity['inReplyTo'];
        
        if(!Helpers::statusFirstOrFetch($activity['url'], true)) {
            $this->logger->delete();
            return;
        }

        $this->logger->to_id = $this->profile->id;
        $this->logger->from_id = $actor->id;
        $this->logger->processed_at = Carbon::now();
        $this->logger->save();
    }

    public function handleNoteCreate()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        if(!$actor || $actor->domain == null) {
            return;
        }

        if(Helpers::userInAudience($this->profile, $this->payload) == false) {
            //Log::error('AP:inbox:userInAudience:false - Activity#'.$this->logger->id);
            $logger = Activity::find($this->logger->id);
            $logger->delete();
            return;
        }

        if(Status::whereUrl($activity['url'])->exists()) {
            return;
        }

        $status = DB::transaction(function() use($activity, $actor) {
            $status = new Status;
            $status->profile_id = $actor->id;
            $status->caption = strip_tags($activity['content']);
            $status->visibility = $status->scope = 'public';
            $status->url = $activity['url'];
            $status->save();
            return $status;
        });

        Helpers::importNoteAttachment($activity, $status);

        $logger = Activity::find($this->logger->id);
        $logger->to_id = $this->profile->id;
        $logger->from_id = $actor->id;
        $logger->processed_at = Carbon::now();
        $logger->save();
    }

    public function handleFollowActivity()
    {
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        if(!$actor || $actor->domain == null) {
            return;
        }
        $target = $this->profile;
        if($target->is_private == true) {
            // make follow request
            FollowRequest::firstOrCreate([
                'follower_id' => $actor->id,
                'following_id' => $target->id
            ]);
            // todo: send notification
        } else {
            // store new follower
            $follower = Follower::firstOrCreate([
                'profile_id' => $actor->id,
                'following_id' => $target->id,
                'local_profile' => empty($actor->domain)
            ]);
            if($follower->wasRecentlyCreated == false) {
                $this->logger->delete();
                return;
            }
            // send notification
            $notification = new Notification();
            $notification->profile_id = $target->id;
            $notification->actor_id = $actor->id;
            $notification->action = 'follow';
            $notification->message = $follower->toText();
            $notification->rendered = $follower->toHtml();
            $notification->item_id = $target->id;
            $notification->item_type = "App\Profile";
            $notification->save();

            \Cache::forever('notification.'.$notification->id, $notification);

            $redis = Redis::connection();

            $nkey = config('cache.prefix').':user.'.$target->id.'.notifications';
            $redis->lpush($nkey, $notification->id);
            
            // send Accept to remote profile
            $accept = [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id'       => $follower->permalink('/accept'),
                'type'     => 'Accept',
                'actor'    => $target->permalink(),
                'object'   => [
                    'id' => $this->payload['id'],
                    'type'  => 'Follow',
                    'actor' => $target->permalink(),
                    'object' => $actor->permalink()
                ]
            ];
            Helpers::sendSignedObject($target, $actor->inbox_url, $accept);
        }
        $this->logger->to_id = $target->id;
        $this->logger->from_id = $actor->id;
        $this->logger->processed_at = Carbon::now();
        $this->logger->save();
    }

    public function handleAnnounceActivity()
    {

    }

    public function handleAcceptActivity()
    {

    }

    public function handleDeleteActivity()
    {

    }

    public function handleLikeActivity()
    {
        $actor = $this->payload['actor'];
        $profile = self::actorFirstOrCreate($actor);
        $obj = $this->payload['object'];
        if(Helpers::validateLocalUrl($obj) == false) {
            return;
        }
        $status = Helpers::statusFirstOrFetch($obj);
        $like = Like::firstOrCreate([
            'profile_id' => $profile->id,
            'status_id' => $status->id
        ]);

        if($like->wasRecentlyCreated == false) {
            return;
        }
        LikePipeline::dispatch($like);
        $this->logger->to_id = $status->profile_id;
        $this->logger->from_id = $profile->id;
        $this->logger->processed_at = Carbon::now();
        $this->logger->save();
    }


    public function handleRejectActivity()
    {

    }

    public function handleUndoActivity()
    {
        $actor = $this->payload['actor'];
        $profile = self::actorFirstOrCreate($actor);
        $obj = $this->payload['object'];
        $status = Helpers::statusFirstOrFetch($obj['object']);

        switch ($obj['type']) {
            case 'Like':
                Like::whereProfileId($profile->id)
                    ->whereStatusId($status->id)
                    ->delete();
                break;
        }

        $this->logger->to_id = $status->profile_id;
        $this->logger->from_id = $profile->id;
        $this->logger->processed_at = Carbon::now();
        $this->logger->save();
    }
}
