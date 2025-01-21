<?php

namespace App\Transformer\Api;

use App\Notification;
use App\Services\AccountService;
use App\Services\RelationshipService;
use App\Services\StatusService;
use League\Fractal;

class NotificationTransformer extends Fractal\TransformerAbstract
{
    public function transform(Notification $notification)
    {
        $res = [
            'id' => (string) $notification->id,
            'type' => $this->replaceTypeVerb($notification->action),
            'created_at' => (string) str_replace('+00:00', 'Z', $notification->created_at->format(DATE_RFC3339_EXTENDED)),
        ];

        $n = $notification;

        if ($n->actor_id) {
            $res['account'] = AccountService::get($n->actor_id);
            if ($n->profile_id != $n->actor_id) {
                $res['relationship'] = RelationshipService::get($n->actor_id, $n->profile_id);
            }
        }

        if ($n->item_id && $n->item_type == 'App\Status') {
            $res['status'] = StatusService::get($n->item_id, false);
        }

        if ($n->item_id && $n->item_type == 'App\ModLog') {
            $ml = $n->item;
            if ($ml && $ml->object_uid) {
                $res['modlog'] = [
                    'id' => $ml->object_uid,
                    'url' => url('/i/admin/users/modlogs/'.$ml->object_uid),
                ];
            }
        }

        if ($n->item_id && $n->item_type == 'App\MediaTag') {
            $ml = $n->item;
            if ($ml && $ml->tagged_username) {
                $np = StatusService::get($ml->status_id, false);
                if ($np && isset($np['id'])) {
                    $res['tagged'] = [
                        'username' => $ml->tagged_username,
                        'post_url' => $np['url'],
                        'status_id' => $ml->status_id,
                        'profile_id' => $ml->profile_id,
                    ];
                }
            }
        }

        return $res;
    }

    public function replaceTypeVerb($verb)
    {
        $verbs = [
            'dm' => 'direct',
            'follow' => 'follow',
            'mention' => 'mention',
            'reblog' => 'share',
            'share' => 'share',
            'like' => 'favourite',
            'comment' => 'comment',
            'admin.user.modlog.comment' => 'modlog',
            'tagged' => 'tagged',
            'story:react' => 'story:react',
            'story:comment' => 'story:comment',
        ];

        if (! isset($verbs[$verb])) {
            return $verb;
        }

        return $verbs[$verb];
    }
}
