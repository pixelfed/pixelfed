<?php

namespace App\Util\ActivityPub;

use Cache, DB, Log, Purify, Redis, Validator;
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

use App\Util\ActivityPub\Validator\{
    Follow
};

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
                if(Accept::validate($this->payload) == false) { return; }
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
        $url = $activity['id'];
        
        if(!Helpers::statusFirstOrFetch($url, true)) {
            return;
        }
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
            return;
        }

        $url = $activity['id'];
        if(Status::whereUrl($url)->exists()) {
            return;
        }

        $status = DB::transaction(function() use($activity, $actor, $url) {
            $caption = str_limit(strip_tags($activity['content']), config('pixelfed.max_caption_length'));
            $status = new Status;
            $status->profile_id = $actor->id;
            $status->caption = strip_tags($caption);
            $status->rendered = Purify::clean($caption);
            $status->visibility = $status->scope = 'public';
            $status->uri = $url;
            $status->url = $url;
            $status->save();
            return $status;
        });

        Helpers::importNoteAttachment($activity, $status);
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
            if($follower->wasRecentlyCreated == true) {
                // send notification
                Notification::firstOrCreate([
                    'profile_id' => $target->id,
                    'actor_id' => $actor->id,
                    'action' => 'follow',
                    'message' => $follower->toText(),
                    'rendered' => $follower->toHtml(),
                    'item_id' => $target->id,
                    'item_type' => 'App\Profile'
                ]);
            }
            $payload = $this->payload;
            // send Accept to remote profile
            $accept = [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id'       => $target->permalink().'#accepts/follows/' . $follower->id,
                'type'     => 'Accept',
                'actor'    => $target->permalink(),
                'object'   => $payload
            ];
            Helpers::sendSignedObject($target, $actor->inbox_url, $accept);
        }
    }

    public function handleAnnounceActivity()
    {
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        $activity = $this->payload['object'];
        if(!$actor || $actor->domain == null) {
            return;
        }
        if(Helpers::validateLocalUrl($activity) == false) {
            return;
        }
        $parent = Helpers::statusFirstOrFetch($activity, true);
        if(!$parent) {
            return;
        }
        $status = Status::firstOrCreate([
            'profile_id' => $actor->id,
            'reblog_of_id' => $parent->id,
            'type' => 'reply'
        ]);
        Notification::firstOrCreate([
            'profile_id' => $parent->profile->id,
            'actor_id' => $actor->id,
            'action' => 'share',
            'message' => $status->replyToText(),
            'rendered' => $status->replyToHtml(),
            'item_id' => $parent->id,
            'item_type' => 'App\Status'
        ]);
    }

    public function handleAcceptActivity()
    {
        $actor = $this->payload['actor'];
        $obj = $this->payload['object'];
        switch ($obj['type']) {
            case 'Follow':
                $accept = [
                    '@context' => 'https://www.w3.org/ns/activitystreams',
                    'id'       => $target->permalink().'#accepts/follows/' . $follower->id,
                    'type'     => 'Accept',
                    'actor'    => $target->permalink(),
                    'object'   => [
                        'id' => $actor->permalink('#follows/'.$target->id),
                        'type'  => 'Follow',
                        'actor' => $actor->permalink(),
                        'object' => $target->permalink()
                    ]
                ];
                break;
            
            default:
                # code...
                break;
        }
    }

    public function handleDeleteActivity()
    {
        $actor = $this->payload['actor'];
        $obj = $this->payload['object'];
        if(is_string($obj) && Helpers::validateUrl($obj)) {
            // actor object detected
            // todo delete actor
        } else if (is_array($obj) && isset($obj['type']) && $obj['type'] == 'Tombstone') {
            // tombstone detected
            $status = Status::whereUri($obj['id'])->firstOrFail();
            $status->forceDelete();
        }
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
    }


    public function handleRejectActivity()
    {

    }

    public function handleUndoActivity()
    {
        $actor = $this->payload['actor'];
        $profile = self::actorFirstOrCreate($actor);
        $obj = $this->payload['object'];

        switch ($obj['type']) {
            case 'Accept':
                break;
                
            case 'Announce':
                break;

            case 'Block':
                break;

            case 'Follow':
                $following = self::actorFirstOrCreate($obj['object']);
                Follower::whereProfileId($profile->id)
                    ->whereFollowingId($following->id)
                    ->delete();
                break;
                
            case 'Like':
                $status = Helpers::statusFirstOrFetch($obj['object']);
                Like::whereProfileId($profile->id)
                    ->whereStatusId($status->id)
                    ->forceDelete();
                Notification::whereProfileId($status->profile->id)
                    ->whereActorId($profile->id)
                    ->whereAction('like')
                    ->whereItemId($status->id)
                    ->whereItemType('App\Status')
                    ->forceDelete();
                break;
        }

    }
}
