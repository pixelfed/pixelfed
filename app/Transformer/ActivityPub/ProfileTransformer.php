<?php

namespace App\Transformer\ActivityPub;

use App\Profile;
use League\Fractal;
use App\Services\AccountService;

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
                    '@type' => '@id'
              ],
              'movedTo' => [
                    '@id' => 'as:movedTo',
                    '@type' => '@id'
              ],
              'indexable' => 'toot:indexable',
            ],
          ],
          'id'                        => $profile->permalink(),
          'type'                      => 'Person',
          'following'                 => $profile->permalink('/following'),
          'followers'                 => $profile->permalink('/followers'),
          'inbox'                     => $profile->permalink('/inbox'),
          'outbox'                    => $profile->permalink('/outbox'),
          'preferredUsername'         => $profile->username,
          'name'                      => $profile->name,
          'summary'                   => $profile->bio,
          'url'                       => $profile->url(),
          'manuallyApprovesFollowers' => (bool) $profile->is_private,
          'indexable'                 => (bool) $profile->indexable,
          'publicKey' => [
            'id'           => $profile->permalink().'#main-key',
            'owner'        => $profile->permalink(),
            'publicKeyPem' => $profile->public_key,
          ],
          'icon' => [
            'type'      => 'Image',
            'mediaType' => 'image/jpeg',
            'url'       => $profile->avatarUrl(),
          ],
          'endpoints' => [
            'sharedInbox' => config('app.url') . '/f/inbox'
          ]
      ];

      if($profile->aliases->count()) {
        $res['alsoKnownAs'] = $profile->aliases->map(fn($alias) => $alias->uri);
      }

      if($profile->moved_to_profile_id) {
        $res['movedTo'] = AccountService::get($profile->moved_to_profile_id)['url'];
      }

      return $res;
    }
}
