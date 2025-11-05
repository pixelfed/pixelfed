<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupPost;
use App\Models\InstanceActor;
use App\Services\MediaService;
use App\Status;
use App\Util\Lexer\Autolink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GroupFederationController extends Controller
{


    public function showGroupObject($group)
    {
        return Cache::remember('ap:groups:object:'.$group->id, 3600, function () use ($group) {
            return [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $group->url(),
                'inbox' => $group->permalink('/inbox'),
                'name' => $group->name,
                'outbox' => $group->permalink('/outbox'),
                'summary' => $group->description,
                'type' => 'Group',
                'attributedTo' => [
                    'type' => 'Person',
                    'id' => $group->admin->permalink(),
                ],
                // 'endpoints' => [
                // 	'sharedInbox' => config('app.url') . '/f/inbox'
                // ],
                'preferredUsername' => 'gid_'.$group->id,
                'publicKey' => [
                    'id' => $group->permalink('#main-key'),
                    'owner' => $group->permalink(),
                    'publicKeyPem' => InstanceActor::first()->public_key,
                ],
                'url' => $group->permalink(),
            ];

            if ($group->metadata && isset($group->metadata['avatar'])) {
                $res['icon'] = [
                    'type' => 'Image',
                    'url' => $group->metadata['avatar']['url'],
                ];
            }

            if ($group->metadata && isset($group->metadata['header'])) {
                $res['image'] = [
                    'type' => 'Image',
                    'url' => $group->metadata['header']['url'],
                ];
            }
            ksort($res);

            return $res;
        });
    }
}
