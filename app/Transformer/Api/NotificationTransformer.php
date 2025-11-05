<?php

namespace App\Transformer\Api;

use App\Notification;
use App\Services\AccountService;
use App\Services\RelationshipService;
use App\Services\StatusService;
use League\Fractal;

class NotificationTransformer extends Fractal\TransformerAbstract
{


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
