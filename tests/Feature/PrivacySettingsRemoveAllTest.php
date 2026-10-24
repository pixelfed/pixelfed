<?php

use App\Jobs\HomeFeedPipeline\FeedUnfollowPipeline;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| POST /settings/privacy/account — remove-all
|--------------------------------------------------------------------------
|
| The "Remove existing followers" option must delete every follower (across
| multiple chunks), dispatch a FeedUnfollowPipeline per follower, and flip the
| account to private + non-suggestable. A restrictive ->select() on the
| chunkById query previously omitted the primary key, so chunkById threw a
| RuntimeException after the first 100 rows -> 500, no deletion, no privacy
| switch.
|
*/

describe('remove-all', function () {
    it('deletes all followers across chunks and switches the account to private', function () {
        Queue::fake();

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;
        $profile->update(['is_private' => false, 'is_suggestable' => true]);

        // More than one chunk (chunkById uses 100) to exercise pagination past
        // the first chunk, which is exactly where the missing-key bug threw.
        $followerCount = 150;
        for ($i = 0; $i < $followerCount; $i++) {
            Follower::create([
                'profile_id' => Profile::factory()->create()->id,
                'following_id' => $profile->id,
                'local_profile' => true,
            ]);
        }

        expect(Follower::whereFollowingId($profile->id)->count())->toBe($followerCount);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('settings.privacy.account'), [
                'mode' => 'remove-all',
                'duration' => 60,
            ])
            ->assertOk();

        // A job per removed follower, across all chunks (not just the first 100).
        Queue::assertPushed(FeedUnfollowPipeline::class, $followerCount);

        // Every follower row is gone.
        expect(Follower::whereFollowingId($profile->id)->count())->toBe(0);

        // The account was switched to private and removed from the directory.
        $profile->refresh();
        expect((bool) $profile->is_private)->toBeTrue()
            ->and((bool) $profile->is_suggestable)->toBeFalse();
    });

    it('requires password confirmation (dangerzone) before removing followers', function () {
        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;
        $profile->update(['is_private' => false, 'is_suggestable' => true]);

        Follower::create([
            'profile_id' => Profile::factory()->create()->id,
            'following_id' => $profile->id,
            'local_profile' => true,
        ]);

        // No confirmed-password session: dangerzone must redirect to the confirm
        // page and the destructive action must not run.
        $this->actingAs($user)
            ->post(route('settings.privacy.account'), [
                'mode' => 'remove-all',
                'duration' => 60,
            ])
            ->assertRedirect(route('password.confirm'));

        expect(Follower::whereFollowingId($profile->id)->count())->toBe(1);
        $profile->refresh();
        expect((bool) $profile->is_private)->toBeFalse();
    });
});
