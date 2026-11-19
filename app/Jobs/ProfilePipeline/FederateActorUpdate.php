<?php

namespace App\Jobs\ProfilePipeline;

use App\Models\Profile;
use App\Services\AccountService;
use App\Services\ActivityPubFanoutService;
use App\Services\FollowerService;
use App\Transformer\ActivityPub\ProfileTransformer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class FederateActorUpdate implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const AS_PUBLIC = 'https://www.w3.org/ns/activitystreams#Public';

    public $tries = 1;

    public $uniqueFor = 600;

    public function __construct(public int $profileId) {}

    public function uniqueId(): string
    {
        return (string) $this->profileId;
    }

    public function handle(): void
    {
        if (! config_cache('federation.activitypub.enabled')) {
            return;
        }

        $profile = Profile::whereNull('domain')->whereNull('status')->find($this->profileId);

        if (! $profile || ! $profile->private_key) {
            return;
        }

        $inboxes = FollowerService::audience($profile->id);

        if (! $inboxes) {
            return;
        }

        AccountService::del($profile->id);

        ActivityPubFanoutService::dispatch($profile, self::activity($profile), $inboxes);
    }

    public static function activity(Profile $profile): array
    {
        $actor = (new Manager)->createData(new Item($profile, new ProfileTransformer))->toArray()['data'];
        $context = $actor['@context'];
        unset($actor['@context']);

        return [
            '@context' => $context,
            'id' => $profile->permalink('#updates/'.now()->getTimestampMs()),
            'type' => 'Update',
            'actor' => $profile->permalink(),
            'to' => [self::AS_PUBLIC],
            'object' => $actor,
        ];
    }
}
