<?php

use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\Account\AccountStatService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Profile Count Reconciliation
|--------------------------------------------------------------------------
|
| Tests for the shared AccountStatService recompute helpers used by both the
| scheduled app:account-post-count-stat-update command and fix:profilecounts.
| status_count must mirror the increment logic (media post types only).
|
*/

describe('AccountStatService recompute helpers', function () {
    it('counts only media post types toward status_count', function () {
        $user = User::factory()->create();
        $user->refresh();
        $pid = $user->profile->id;

        // 2 photos + 1 video = 3 countable; text + reply are NOT counted.
        Status::factory()->count(2)->photo()->create(['profile_id' => $pid]);
        Status::factory()->video()->create(['profile_id' => $pid]);
        Status::factory()->create(['profile_id' => $pid, 'type' => 'text']);
        Status::factory()->reply()->create(['profile_id' => $pid]);

        expect(AccountStatService::recalculateStatusCount($pid))->toBe(3);
    });

    it('counts followers and following from the followers table', function () {
        $a = User::factory()->create();
        $a->refresh();
        $b = User::factory()->create();
        $b->refresh();
        $c = User::factory()->create();
        $c->refresh();

        // b and c follow a; a follows c.
        Follower::create(['profile_id' => $b->profile->id, 'following_id' => $a->profile->id]);
        Follower::create(['profile_id' => $c->profile->id, 'following_id' => $a->profile->id]);
        Follower::create(['profile_id' => $a->profile->id, 'following_id' => $c->profile->id]);

        expect(AccountStatService::recalculateFollowerCount($a->profile->id))->toBe(2);
        expect(AccountStatService::recalculateFollowingCount($a->profile->id))->toBe(1);
    });
});

describe('AccountStatService::reconcileProfileCounts', function () {
    it('fixes all drifted columns and reports the summary', function () {
        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        Status::factory()->count(2)->photo()->create(['profile_id' => $profile->id]);

        // Deliberately set wrong cached values.
        $profile->status_count = 99;
        $profile->followers_count = 42;
        $profile->following_count = 7;
        $profile->save();

        $summary = AccountStatService::reconcileProfileCounts($profile->fresh());

        expect($summary['statuses']['drifted'])->toBeTrue();
        expect($summary['statuses']['live'])->toBe(2);
        expect($summary['followers']['live'])->toBe(0);
        expect($summary['following']['live'])->toBe(0);

        $profile->refresh();
        expect((int) $profile->status_count)->toBe(2);
        expect((int) $profile->followers_count)->toBe(0);
        expect((int) $profile->following_count)->toBe(0);
    });

    it('reports no drift and writes nothing when counts are correct', function () {
        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        Status::factory()->photo()->create(['profile_id' => $profile->id]);
        $profile->status_count = 1;
        $profile->followers_count = 0;
        $profile->following_count = 0;
        $profile->save();
        $updatedAt = $profile->fresh()->updated_at;

        $summary = AccountStatService::reconcileProfileCounts($profile->fresh());

        expect($summary['statuses']['drifted'])->toBeFalse();
        expect($summary['followers']['drifted'])->toBeFalse();
        expect($summary['following']['drifted'])->toBeFalse();

        // No write should have occurred (updated_at unchanged).
        expect($profile->fresh()->updated_at->eq($updatedAt))->toBeTrue();
    });

    it('restricts reconciliation to the requested metrics only', function () {
        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        Status::factory()->count(3)->photo()->create(['profile_id' => $profile->id]);

        $profile->status_count = 0;      // drifted, should be fixed
        $profile->followers_count = 50;  // drifted, but must be left alone
        $profile->save();

        $summary = AccountStatService::reconcileProfileCounts($profile->fresh(), ['statuses']);

        expect($summary)->toHaveKey('statuses');
        expect($summary)->not->toHaveKey('followers');

        $profile->refresh();
        expect((int) $profile->status_count)->toBe(3);
        expect((int) $profile->followers_count)->toBe(50);
    });

    it('returns an empty summary for a missing profile id', function () {
        expect(AccountStatService::reconcileProfileCounts(999999999999))->toBe([]);
    });
});

describe('fix:profilecounts command', function () {
    it('is silent for an in-sync profile and reports drift otherwise', function () {
        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;
        Status::factory()->count(2)->photo()->create(['profile_id' => $profile->id]);
        $profile->status_count = 5; // drift
        $profile->save();

        $this->artisan('fix:profilecounts', ['id' => (string) $profile->id])
            ->expectsOutputToContain('drift detected')
            ->assertExitCode(0);

        // Now in sync -> no drift output.
        $this->artisan('fix:profilecounts', ['id' => (string) $profile->id])
            ->doesntExpectOutputToContain('drift detected')
            ->assertExitCode(0);
    });

    it('does not modify anything in dry-run mode', function () {
        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;
        Status::factory()->photo()->create(['profile_id' => $profile->id]);
        $profile->status_count = 88;
        $profile->save();

        $this->artisan('fix:profilecounts', ['id' => (string) $profile->id, '--dry-run' => true])
            ->assertExitCode(0);

        expect((int) $profile->fresh()->status_count)->toBe(88);
    });
});
