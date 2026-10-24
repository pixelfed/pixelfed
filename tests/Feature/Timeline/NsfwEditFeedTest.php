<?php

use App\Models\Status;
use App\Models\User;
use App\Services\PublicTimelineService;
use App\Services\Status\UpdateStatusService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['instance.hide_nsfw_on_public_feeds' => true]);
    Redis::del(PublicTimelineService::CACHE_KEY);
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
});

function editableLocalStatus(int $profileId): Status
{
    $s = Status::factory()->create([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'is_nsfw' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
    ]);
    StatusService::get($s->id);

    return $s;
}

function localFeedHas(int $sid): bool
{
    return in_array(
        (string) $sid,
        collect(PublicTimelineService::get(0, -1))->map(fn ($v) => (string) $v)->all(),
        true
    );
}

it('removes a post from the local timeline when edited to sensitive', function () {
    $author = User::factory()->create();
    $author->refresh();

    $status = editableLocalStatus($author->profile_id);
    PublicTimelineService::add($status->id);

    UpdateStatusService::call($status, ['sensitive' => true]);

    expect(localFeedHas((int) $status->id))->toBeFalse();
});

it('restores a post to the local timeline when sensitivity is cleared', function () {
    $author = User::factory()->create();
    $author->refresh();

    $status = editableLocalStatus($author->profile_id);
    $status->is_nsfw = true;
    $status->save();
    StatusService::del($status->id);

    expect(localFeedHas((int) $status->id))->toBeFalse();

    UpdateStatusService::call($status->fresh(), ['sensitive' => false]);

    expect(localFeedHas((int) $status->id))->toBeTrue();
});
