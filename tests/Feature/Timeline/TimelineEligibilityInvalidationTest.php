<?php

use App\Http\Controllers\AdminController;
use App\Jobs\StatusPipeline\StatusRemoteUpdatePipeline;
use App\Models\Profile;
use App\Models\Report;
use App\Models\Status;
use App\Models\User;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Global-feed eligibility invalidation
|--------------------------------------------------------------------------
|
| The local and network feeds only hold a status while it remains eligible.
| Two moderation-adjacent events can flip that eligibility after a status is
| already cached: a remote sensitivity edit (when the instance hides NSFW on
| public feeds) and an admin report unlisting. These walks drive the real
| entry points - the remote update pipeline and the per-status report action -
| and assert that membership is reconciled against the exact same eligibility
| the live-insertion and warm paths use, that a status unlisted on one locale
| leaves the other locale's feed untouched, and that unrelated eligible members
| never move.
|
*/

const EIV_BANNED_DOMAINS = 'instances:banned:domains';

const EIV_UNLISTED_DOMAINS = 'instances:unlisted:domains';

beforeEach(function () {
    config(['instance.timeline.network.max_hours_old' => 2160]);
    config(['instance.timeline.network.cache_dropoff' => 400]);
    config(['instance.hide_nsfw_on_public_feeds' => true]);
    Redis::flushdb();
    Cache::flush();
});

afterEach(function () {
    Redis::flushdb();
    Cache::flush();
});

/**
 * Highest-score-first display order as Redis serves it, as integers.
 */
function eligibilityOrder(string $key): array
{
    return collect(Redis::zrevrange($key, 0, -1))
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();
}

/**
 * A remote public photo keyed to a domain, object url, and publish time. The
 * StatusService read primes the created_at score the timeline services use.
 */
function eligibilityRemoteStatus(string $domain, string $createdAt, array $overrides = []): Status
{
    $author = Profile::factory()->create(['user_id' => null, 'domain' => $domain]);
    $objectUrl = 'https://'.$domain.'/users/bob/statuses/'.bin2hex(random_bytes(6));

    $status = Status::factory()->create(array_merge([
        'profile_id' => $author->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'uri' => $objectUrl,
        'object_url' => $objectUrl,
        'created_at' => $createdAt,
    ], $overrides));

    StatusService::get($status->id, false);
    StatusService::getMastodon($status->id, false);

    return $status;
}

/**
 * A local public photo with an explicit publish time.
 */
function eligibilityLocalStatus(int $profileId, string $createdAt, array $overrides = []): Status
{
    $status = Status::factory()->create(array_merge([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'uri' => null,
        'object_url' => null,
        'created_at' => $createdAt,
    ], $overrides));

    StatusService::get($status->id, false);
    StatusService::getMastodon($status->id, false);

    return $status;
}

/**
 * Deliver a remote sensitivity edit for an already-stored status.
 */
function deliverSensitivity(Status $status, bool $sensitive): Status
{
    (new StatusRemoteUpdatePipeline([
        'id' => $status->object_url,
        'type' => 'Update',
        'content' => 'edited caption',
        'sensitive' => $sensitive,
    ]))->handle();

    return $status->fresh();
}

/**
 * Run the per-status report unlist moderation action through its real handler.
 */
function unlistReportedStatus(Status $status): void
{
    $reporter = User::factory()->create();
    $reporter->refresh();

    $report = Report::create([
        'profile_id' => $reporter->profile_id,
        'user_id' => $reporter->id,
        'object_id' => $status->id,
        'object_type' => 'App\Status',
        'reported_profile_id' => $status->profile_id,
        'type' => 'spam',
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $admin->refresh();

    // The moderation handler stamps a mod-log row from request()->user(), so
    // resolve the admin onto the active request before invoking it.
    request()->setUserResolver(fn () => $admin);

    $controller = new AdminController;
    $method = (new ReflectionClass($controller))->getMethod('reportsHandleStatusAction');
    $method->setAccessible(true);

    $method->invoke($controller, $report, 'unlist');
}

it('removes and restores network membership after remote sensitivity transitions', function () {
    // A single remote photo plus an unrelated eligible remote photo that must
    // never move across either transition.
    $subject = eligibilityRemoteStatus('remote.example', now()->subMinutes(20)->toDateTimeString());
    $bystander = eligibilityRemoteStatus('other.example', now()->subMinutes(40)->toDateTimeString());

    // Live insertion through the real ingest helper, honouring the same
    // eligibility and filtered-domain gates delivery applies.
    foreach ([$subject, $bystander] as $s) {
        Helpers::handleStatusPostProcessing($s->fresh(), $s->profile_id, $s->uri);
    }

    $networkKey = NetworkTimelineService::CACHE_KEY;

    expect(eligibilityOrder($networkKey))->toContain((int) $subject->id);
    expect(eligibilityOrder($networkKey))->toContain((int) $bystander->id);

    // false -> true: the subject becomes sensitive. With NSFW hidden on public
    // feeds it is no longer eligible, so it must leave the network feed. The
    // oracle is the same eligibility check live insertion and warm use.
    $afterHide = deliverSensitivity($subject, true);
    expect((bool) $afterHide->is_nsfw)->toBeTrue();
    expect(Helpers::isEligibleForNetwork($afterHide))->toBeFalse();

    expect(eligibilityOrder($networkKey))->not->toContain((int) $subject->id);
    expect(eligibilityOrder($networkKey))->toContain((int) $bystander->id);

    // true -> false: the subject clears sensitivity and becomes eligible again,
    // so it must be restored to the network feed.
    $afterShow = deliverSensitivity($subject, false);
    expect((bool) $afterShow->is_nsfw)->toBeFalse();
    expect(Helpers::isEligibleForNetwork($afterShow))->toBeTrue();

    expect(eligibilityOrder($networkKey))->toContain((int) $subject->id);
    expect(eligibilityOrder($networkKey))->toContain((int) $bystander->id);
});

it('keeps an ineligible remote status absent when sensitivity is cleared', function () {
    Cache::put(EIV_BANNED_DOMAINS, ['banned.example'], 1209600);

    // A banned-domain remote photo is ineligible for the network feed by
    // domain alone, independent of its sensitivity. An eligible control from an
    // allowed domain gives the feed a real member to compare against.
    $control = eligibilityRemoteStatus('remote.example', now()->subMinutes(15)->toDateTimeString());
    $filtered = eligibilityRemoteStatus('banned.example', now()->subMinutes(10)->toDateTimeString(), [
        'is_nsfw' => true,
    ]);

    foreach ([$control, $filtered] as $s) {
        Helpers::handleStatusPostProcessing($s->fresh(), $s->profile_id, $s->uri);
    }

    $networkKey = NetworkTimelineService::CACHE_KEY;

    // The filtered-domain status never entered the feed even though its only
    // disqualifier at ingest was the domain filter.
    expect(eligibilityOrder($networkKey))->toContain((int) $control->id);
    expect(eligibilityOrder($networkKey))->not->toContain((int) $filtered->id);

    // Clearing sensitivity flips the NSFW gate open, but the banned domain
    // still disqualifies it, so it must stay absent while the control remains.
    $cleared = deliverSensitivity($filtered, false);
    expect((bool) $cleared->is_nsfw)->toBeFalse();

    expect(eligibilityOrder($networkKey))->not->toContain((int) $filtered->id);
    expect(eligibilityOrder($networkKey))->toContain((int) $control->id);
});

it('removes report-unlisted local and remote statuses from their locality-specific global feeds', function () {
    $localAuthor = User::factory()->create();
    $localAuthor->refresh();

    // A local public photo lives in the local feed; a sibling local photo is
    // the unrelated member that must survive the unlisting untouched.
    $localTarget = eligibilityLocalStatus($localAuthor->profile_id, now()->subMinutes(20)->toDateTimeString());
    $localKeep = eligibilityLocalStatus($localAuthor->profile_id, now()->subMinutes(30)->toDateTimeString());

    // A remote public photo lives in the network feed with its own unrelated
    // sibling from a different domain.
    $remoteTarget = eligibilityRemoteStatus('remote.example', now()->subMinutes(20)->toDateTimeString());
    $remoteKeep = eligibilityRemoteStatus('other.example', now()->subMinutes(30)->toDateTimeString());

    foreach ([$localTarget, $localKeep] as $s) {
        PublicTimelineService::add($s->id);
    }
    foreach ([$remoteTarget, $remoteKeep] as $s) {
        NetworkTimelineService::add($s->id);
    }

    $publicKey = PublicTimelineService::CACHE_KEY;
    $networkKey = NetworkTimelineService::CACHE_KEY;

    expect(eligibilityOrder($publicKey))->toBe([(int) $localTarget->id, (int) $localKeep->id]);
    expect(eligibilityOrder($networkKey))->toBe([(int) $remoteTarget->id, (int) $remoteKeep->id]);

    // Unlisting the local status drops only the local member and never reaches
    // the network feed.
    unlistReportedStatus($localTarget);

    expect(eligibilityOrder($publicKey))->toBe([(int) $localKeep->id]);
    expect(eligibilityOrder($networkKey))->toBe([(int) $remoteTarget->id, (int) $remoteKeep->id]);

    // Unlisting the remote status drops only the network member and leaves the
    // local feed untouched.
    unlistReportedStatus($remoteTarget);

    expect(eligibilityOrder($networkKey))->toBe([(int) $remoteKeep->id]);
    expect(eligibilityOrder($publicKey))->toBe([(int) $localKeep->id]);
});
