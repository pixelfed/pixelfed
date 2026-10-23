<?php

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
| NotificationTransformer status hydration (pixelfed#7195)
|--------------------------------------------------------------------------
|
| Notification::item_type is stored verbatim. Rows created before the
| App\ -> App\Models\ namespace migration hold the legacy 'App\Status'
| value (now a morph-map alias), while new rows hold App\Models\Status.
| A strict `== Status::class` comparison silently skipped status hydration
| for the legacy rows, so favourite/comment/mention notifications came back
| with no attached status and the web UI dropped them (endless loading).
|
| These transform the notification directly (no Redis-backed service cache)
| so the assertion targets the transformer contract itself.
|
*/

function transformNotification(Notification $n): array
{
    $fractal = new Manager;
    $fractal->setSerializer(new ArraySerializer);

    return $fractal->createData(new Item($n, new NotificationTransformer))->toArray();
}

/**
 * Persist a like notification while forcing the raw item_type string, so we
 * can reproduce legacy ('App\Status') vs current (App\Models\Status) rows.
 */
function makeLikeNotification(string $itemType): array
{
    $user = User::factory()->create();
    $user->refresh();
    $actor = User::factory()->create();
    $actor->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'scope' => 'public',
    ]);

    $n = new Notification;
    $n->profile_id = $user->profile_id;
    $n->actor_id = $actor->profile_id;
    $n->action = 'like';
    $n->item_id = $status->id;
    $n->item_type = $itemType;
    $n->save();

    return [$n, $status];
}

it('hydrates status for a legacy App\\Status notification (regression)', function () {
    [$n, $status] = makeLikeNotification('App\Status');

    $res = transformNotification($n);

    expect($res['type'])->toBe('favourite');
    expect($res['status'])->not->toBeNull();
    expect((string) $res['status']['id'])->toBe((string) $status->id);
});

it('hydrates status for a current App\\Models\\Status notification', function () {
    [$n, $status] = makeLikeNotification(Status::class);

    $res = transformNotification($n);

    expect($res['status'])->not->toBeNull();
    expect((string) $res['status']['id'])->toBe((string) $status->id);
});

it('returns a null status for a legacy notification whose status was deleted', function () {
    [$n, $status] = makeLikeNotification('App\Status');
    $status->delete();

    $res = transformNotification($n);

    // The comparison now matches, so hydration is attempted; a deleted status
    // resolves to null rather than being skipped by an unmatched item_type.
    expect($res['status'] ?? null)->toBeNull();
});

it('does not attach a status for a follow notification', function () {
    $user = User::factory()->create();
    $user->refresh();
    $actor = User::factory()->create();
    $actor->refresh();

    $n = new Notification;
    $n->profile_id = $user->profile_id;
    $n->actor_id = $actor->profile_id;
    $n->action = 'follow';
    $n->item_id = $actor->profile_id;
    $n->item_type = 'App\Profile';
    $n->save();

    $res = transformNotification($n);

    expect($res['type'])->toBe('follow');
    expect(array_key_exists('status', $res))->toBeFalse();
});
