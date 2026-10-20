<?php

use App\Models\AdminShadowFilter;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\AdminShadowFilterService;
use App\Services\PublicTimelineService;
use App\Services\Status\UpdateStatusService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['instance.hide_nsfw_on_public_feeds' => true]);
    Redis::del(PublicTimelineService::CACHE_KEY);
    AdminShadowFilterService::refresh();
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
    AdminShadowFilterService::refresh();
});

function reconcileLocalStatus(int $profileId, string $type = 'photo'): Status
{
    $s = Status::factory()->create([
        'profile_id' => $profileId,
        'type' => $type,
        'scope' => 'public',
        'visibility' => 'public',
        'is_nsfw' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
    ]);
    StatusService::get($s->id);

    return $s;
}

function reconcileFeedHas(int $sid): bool
{
    return in_array(
        (string) $sid,
        collect(PublicTimelineService::get(0, -1))->map(fn ($v) => (string) $v)->all(),
        true
    );
}

function shadowFilterProfile(int $profileId): void
{
    AdminShadowFilter::create([
        'item_type' => Profile::class,
        'item_id' => $profileId,
        'active' => 1,
        'hide_from_public_feeds' => true,
    ]);
    AdminShadowFilterService::refresh();
}

it('adds a photo status from an unfiltered profile when sensitivity is cleared', function () {
    $author = User::factory()->create();
    $author->refresh();

    $status = reconcileLocalStatus($author->profile_id);

    expect(reconcileFeedHas((int) $status->id))->toBeFalse();

    UpdateStatusService::call($status->fresh(), ['sensitive' => false]);

    expect(reconcileFeedHas((int) $status->id))->toBeTrue();
});

it('does not add a status from a shadow-filtered profile when sensitivity is cleared', function () {
    $author = User::factory()->create();
    $author->refresh();

    shadowFilterProfile($author->profile_id);
    expect(AdminShadowFilterService::canAddToPublicFeedByProfileId($author->profile_id))->toBeFalse();

    $status = reconcileLocalStatus($author->profile_id);

    UpdateStatusService::call($status->fresh(), ['sensitive' => false]);

    expect(reconcileFeedHas((int) $status->id))->toBeFalse();
});

it('does not add a text-type status when sensitivity is cleared', function () {
    $author = User::factory()->create();
    $author->refresh();

    $status = reconcileLocalStatus($author->profile_id, 'text');

    UpdateStatusService::call($status->fresh(), ['sensitive' => false]);

    expect(reconcileFeedHas((int) $status->id))->toBeFalse();
});

it('removes a status from the feed when sensitivity is toggled on', function () {
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
    StatusService::get($status->id);
    PublicTimelineService::add($status->id);

    expect(reconcileFeedHas((int) $status->id))->toBeTrue();

    UpdateStatusService::call($status->fresh(), ['sensitive' => true]);

    expect(reconcileFeedHas((int) $status->id))->toBeFalse();
});
