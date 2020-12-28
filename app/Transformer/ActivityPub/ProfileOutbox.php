<?php

namespace App\Transformer\ActivityPub;

use App\Profile;
use League\Fractal;
use App\Transformer\ActivityPub\Verb\CreateNote;

class ProfileOutbox extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = ['orderedItems'];

    public function transform(Profile $profile)
    {
        $count = $profile->statuses()->whereHas('media')->count();

        return [
          '@context'     => 'https://www.w3.org/ns/activitystreams',
          'id'           => $profile->permalink('/outbox'),
          'type'         => 'OrderedCollection',
          'totalItems'   => $count,
      ];
    }

    public function includeOrderedItems(Profile $profile)
    {
        $statuses = $profile
          ->statuses()
          ->with('media')
          ->whereScope('public')
          ->orderBy('created_at', 'desc')
          ->paginate(10);

        return $this->collection($statuses, new CreateNote);
    }
}
