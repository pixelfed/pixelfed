<?php

namespace App\Transformer\ActivityPub;

use App\Profile;
use League\Fractal;

class ProfileTransformer extends Fractal\TransformerAbstract
{
    public function transform(Profile $profile)
    {
        return [
          '@context' => [
            'https://www.w3.org/ns/activitystreams',
            'https://w3id.org/security/v1',
            [
              'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
            ],
          ],
          'id'                        => $profile->permalink(),
          'type'                      => 'Person',
          'following'                 => $profile->permalink('/following'),
          'followers'                 => $profile->permalink('/followers'),
          'inbox'                     => $profile->permalink('/inbox'),
          'outbox'                    => $profile->permalink('/outbox'),
          //'featured'                  => $profile->permalink('/collections/featured'),
          'preferredUsername'         => $profile->username,
          'name'                      => $profile->name,
          'summary'                   => $profile->bio,
          'url'                       => $profile->url(),
          'manuallyApprovesFollowers' => (bool) $profile->is_private,
          // 'follower_count' => $profile->followers()->count(),
          // 'following_count' => $profile->following()->count(),
          'publicKey' => [
            'id'           => $profile->permalink().'#main-key',
            'owner'        => $profile->permalink(),
            'publicKeyPem' => $profile->public_key,
          ],
          // remove shared inbox support until proper support
          // 'endpoints' => [
          //   'sharedInbox' => config('routes.api.sharedInbox'),
          // ],
          'icon' => [
            'type'      => 'Image',
            'mediaType' => 'image/jpeg',
            'url'       => $profile->avatarUrl(),
          ],
      ];
    }
}
