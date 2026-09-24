<?php

use App\Models\MediaTag;
use App\Models\Notification;
use App\Models\Status;
use App\Models\User;
use App\Transformer\Api\NotificationTransformer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\ArraySerializer;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NotificationTransformer tagged hydration
|--------------------------------------------------------------------------
|
| Notification::item_type is stored verbatim. Rows created before the
| App\ -> App\Models\ namespace migration hold the legacy 'App\MediaTag'
| value (now a morph-map alias), while new rows hold App\Models\MediaTag.
| A strict `== MediaTag::class` comparison dropped the `tagged` payload for
| legacy rows, so the web UI dereferenced a missing tagged.post_url and the
| Mastodon path could not attach a status to the mention.
|
*/

function transformTaggedNotification(Notification $n): array
{
    $fractal = new Manager;
    $fractal->setSerializer(new ArraySerializer);

    return $fractal->createData(new Item($n, new NotificationTransformer))->toArray();
}

/**
 * Persist a tagged notification while forcing the raw item_type string, so we
 * can reproduce legacy ('App\MediaTag') vs current (App\Models\MediaTag) rows.
 */
function makeTaggedNotification(string $itemType): array
{
    $author = User::factory()->create();
    $author->refresh();
    $tagged = User::factory()->create();
    $tagged->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'scope' => 'public',
        'visibility' => 'public',
    ]);

    $mediaTag = new MediaTag;
    $mediaTag->status_id = $status->id;
    $mediaTag->media_id = 0;
    $mediaTag->profile_id = $tagged->profile_id;
    $mediaTag->tagged_username = $tagged->username;
    $mediaTag->is_public = 1;
    $mediaTag->save();

    $n = new Notification;
    $n->profile_id = $tagged->profile_id;
    $n->actor_id = $author->profile_id;
    $n->action = 'tagged';
    $n->item_id = $mediaTag->id;
    $n->item_type = $itemType;
    $n->save();

    return [$n, $status, $tagged, $mediaTag];
}

it('hydrates the tagged payload for a current App\\Models\\MediaTag notification', function () {
    [$n, $status, $tagged] = makeTaggedNotification(MediaTag::class);

    $res = transformTaggedNotification($n);

    expect($res['type'])->toBe('tagged');
    expect($res['tagged'] ?? null)->not->toBeNull();
    expect($res['tagged']['username'])->toBe($tagged->username);
    expect((string) $res['tagged']['status_id'])->toBe((string) $status->id);
});

it('hydrates the tagged payload for a legacy App\\MediaTag notification (regression)', function () {
    [$n, $status, $tagged] = makeTaggedNotification('App\MediaTag');

    $res = transformTaggedNotification($n);

    expect($res['type'])->toBe('tagged');
    expect($res['tagged'] ?? null)->not->toBeNull();
    expect($res['tagged']['username'])->toBe($tagged->username);
    expect((string) $res['tagged']['status_id'])->toBe((string) $status->id);
});
