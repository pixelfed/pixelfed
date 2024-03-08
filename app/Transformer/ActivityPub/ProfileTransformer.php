<?php

namespace App\Transformer\ActivityPub;

use App\Profile;
use App\Services\AccountService;
use League\Fractal;

class ProfileTransformer extends Fractal\TransformerAbstract
{
    public function transform(Profile $profile)
    {
        $res = [
            '@context' => [
                'https://w3id.org/security/v1',
                'https://www.w3.org/ns/activitystreams',
                [
                    'toot' => 'http://joinmastodon.org/ns#',
                    'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
                    'alsoKnownAs' => [
                        '@id' => 'as:alsoKnownAs',
                        '@type' => '@id',
                    ],
                    'movedTo' => [
                        '@id' => 'as:movedTo',
                        '@type' => '@id',
                    ],
                    'indexable' => 'toot:indexable',
                    'suspended' => 'toot:suspended',
                ],
            ],
            'id' => $profile->permalink(),
            'type' => 'Person',
            'following' => $profile->permalink('/following'),
            'followers' => $profile->permalink('/followers'),
            'inbox' => $profile->permalink('/inbox'),
            'outbox' => $profile->permalink('/outbox'),
            'preferredUsername' => $profile->username,
            'name' => $profile->name,
            'summary' => $profile->bio,
            'url' => $profile->url(),
            'manuallyApprovesFollowers' => (bool) $profile->is_private,
            'indexable' => (bool) $profile->indexable,
            'published' => $profile->created_at->format('Y-m-d').'T00:00:00Z',
            'publicKey' => [
                'id' => $profile->permalink().'#main-key',
                'owner' => $profile->permalink(),
                'publicKeyPem' => $profile->public_key,
            ],
            'icon' => [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => $profile->avatarUrl(),
            ],
            'endpoints' => [
                'sharedInbox' => config('app.url').'/f/inbox',
            ],
        ];

        if ($profile->status === 'delete' || $profile->deleted_at != null) {
            $res['suspended'] = true;
            $res['name'] = '';
            unset($res['icon']);
            $res['summary'] = '';
            $res['indexable'] = false;
            $res['manuallyApprovesFollowers'] = false;
        } else {
            if ($profile->aliases->count()) {
                $res['alsoKnownAs'] = $profile->aliases->map(fn ($alias) => $alias->uri);
            }

            if ($profile->moved_to_profile_id) {
                $movedTo = AccountService::get($profile->moved_to_profile_id);
                if ($movedTo && isset($movedTo['url'], $movedTo['id'])) {
                    $res['movedTo'] = $movedTo['url'];
                }
            }
        }

        return $res;
    }
}
