<?php

use App\Http\Controllers\InternalApiController;
use App\Models\Status;
use App\Models\User;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
});

function callModAction(User $admin, string $action, int $statusId): void
{
    $request = Request::create('/i/moderator/action', 'POST', [
        'action' => $action,
        'item_id' => $statusId,
        'item_type' => 'status',
    ]);
    $request->setUserResolver(fn () => $admin);

    (new InternalApiController)->modAction($request);
}

function publicFeedHas(int $sid): bool
{
    return in_array(
        (string) $sid,
        collect(PublicTimelineService::get(0, -1))->map(fn ($v) => (string) $v)->all(),
        true
    );
}

it('keeps a post in the local timeline after removing a content warning', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $admin->refresh();

    $author = User::factory()->create();
    $author->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'is_nsfw' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
    ]);

    PublicTimelineService::add($status->id);
    StatusService::get($status->id);

    callModAction($admin, 'remcw', $status->id);

    expect(publicFeedHas((int) $status->id))->toBeTrue()
        ->and((bool) $status->fresh()->is_nsfw)->toBeFalse();
});

it('removes a post from the local timeline when adding a content warning', function () {
    config(['instance.hide_nsfw_on_public_feeds' => true]);

    $admin = User::factory()->create(['is_admin' => true]);
    $admin->refresh();

    $author = User::factory()->create();
    $author->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'is_nsfw' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
    ]);

    PublicTimelineService::add($status->id);
    StatusService::get($status->id);

    callModAction($admin, 'addcw', $status->id);

    expect(publicFeedHas((int) $status->id))->toBeFalse();
});
