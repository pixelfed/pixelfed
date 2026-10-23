<?php

namespace App\Transformer\ActivityPub;

use App\Models\Profile;
use App\Services\AccountService;
use App\Services\FeaturedCollectionService;
use App\Util\Media\License;
use League\Fractal;

class ProfileTransformer extends Fractal\TransformerAbstract
{
    public function transform(Profile $profile): array
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
                    'gts' => 'https://gotosocial.org/ns#',
                    'interactionPolicy' => [
                        '@id' => 'gts:interactionPolicy',
                        '@type' => '@id',
                    ],
                    'canFeature' => [
                        '@id' => 'https://w3id.org/fep/7aa9#canFeature',
                        '@type' => '@id',
                    ],
                    'automaticApproval' => [
                        '@id' => 'gts:automaticApproval',
                        '@type' => '@id',
                    ],
                    'manualApproval' => [
                        '@id' => 'gts:manualApproval',
                        '@type' => '@id',
                    ],
                    ...License::ACTOR_CONTEXT_TERMS,
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

            $res['interactionPolicy'] = FeaturedCollectionService::interactionPolicy($profile);

            $preferredLicense = $this->preferredLicense($profile);
            if ($preferredLicense) {
                $res['preferredLicense'] = $preferredLicense;
            }
        }

        return $res;
    }

    /**
     * FEP-6757 preferred license, from the account's default media license.
     *
     * Omitted when the default is all rights reserved.
     */
    protected function preferredLicense(Profile $profile): ?string
    {
        $settings = AccountService::getAccountSettings($profile->id);
        $id = (int) ($settings['default_license'] ?? 1);

        return $id > 1 ? License::uriForId($id) : null;
    }
}
