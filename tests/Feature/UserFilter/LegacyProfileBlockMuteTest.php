<?php

use App\Models\Profile;
use App\Models\User;
use App\Models\UserFilter;
use App\Services\RelationshipService;
use App\Services\UserFilterService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Legacy morph-type block/mute coverage
|--------------------------------------------------------------------------
|
| Rows written before the model namespace move store filterable_type as the
| old alias 'App\Profile' instead of App\Models\Profile. Reads and deletes
| that match only the modern class skipped those rows, so a legacy block or
| mute reported as inactive and could never be removed. These cases cover
| the relationship reporter, the unblock/unmute delete paths, the profile
| id helpers, and the data migration that normalizes the stored value.
|
*/

const LEGACY_TYPE = 'App\Profile';

function seedFilter(int $userId, int $targetId, string $filterType, string $type): UserFilter
{
    $filter = new UserFilter;
    $filter->user_id = $userId;
    $filter->filterable_id = $targetId;
    $filter->filterable_type = $type;
    $filter->filter_type = $filterType;
    $filter->save();

    return $filter;
}

function runMorphNormalization(): void
{
    $migration = require base_path('database/migrations/2026_10_07_000001_normalize_legacy_user_filter_morph_types.php');
    $migration->up();
}

describe('legacy App\Profile rows', function () {
    it('reports blocking and muting true for a legacy row', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();

        seedFilter($user->profile_id, $target->profile_id, 'block', LEGACY_TYPE);
        seedFilter($user->profile_id, $target->profile_id, 'mute', LEGACY_TYPE);

        $rel = RelationshipService::refresh($user->profile_id, $target->profile_id);

        expect($rel['blocking'])->toBeTrue();
        expect($rel['muting'])->toBeTrue();
    });

    it('unblocks and deletes a legacy block row', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();

        seedFilter($user->profile_id, $target->profile_id, 'block', LEGACY_TYPE);
        Passport::actingAs($user, ['write']);

        $this->postJson("/api/v1/accounts/{$target->profile_id}/unblock")
            ->assertOk();

        expect(UserFilter::where('user_id', $user->profile_id)
            ->where('filterable_id', $target->profile_id)
            ->where('filter_type', 'block')
            ->exists()
        )->toBeFalse();
    });

    it('unmutes and deletes a legacy mute row', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();

        seedFilter($user->profile_id, $target->profile_id, 'mute', LEGACY_TYPE);
        Passport::actingAs($user, ['write']);

        $this->postJson("/api/v1/accounts/{$target->profile_id}/unmute")
            ->assertOk();

        expect(UserFilter::where('user_id', $user->profile_id)
            ->where('filterable_id', $target->profile_id)
            ->where('filter_type', 'mute')
            ->exists()
        )->toBeFalse();
    });

    it('includes legacy-blocked and legacy-muted ids in the profile helpers', function () {
        $user = User::factory()->create();
        $user->refresh();
        $blocked = User::factory()->create();
        $blocked->refresh();
        $muted = User::factory()->create();
        $muted->refresh();

        seedFilter($user->profile_id, $blocked->profile_id, 'block', LEGACY_TYPE);
        seedFilter($user->profile_id, $muted->profile_id, 'mute', LEGACY_TYPE);

        $profile = Profile::find($user->profile_id);

        expect($profile->blockedIds()->contains($blocked->profile_id))->toBeTrue();
        expect($profile->mutedIds()->contains($muted->profile_id))->toBeTrue();
    });
});

describe('warm cache eviction on legacy row removal', function () {
    it('evicts a legacy-blocked id from the warm block cache on unblock', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();

        seedFilter($user->profile_id, $target->profile_id, 'block', LEGACY_TYPE);

        expect(collect(UserFilterService::blocks($user->profile_id))->map(fn ($i) => (string) $i))
            ->toContain((string) $target->profile_id);

        Passport::actingAs($user, ['write']);
        $this->postJson("/api/v1/accounts/{$target->profile_id}/unblock")
            ->assertOk();

        expect(collect(UserFilterService::blocks($user->profile_id))->map(fn ($i) => (string) $i))
            ->not->toContain((string) $target->profile_id);
        expect(UserFilterService::blockCount($user->profile_id))->toBe(0);
    });

    it('evicts a legacy-muted id from the warm mute cache on unmute', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();

        seedFilter($user->profile_id, $target->profile_id, 'mute', LEGACY_TYPE);

        expect(collect(UserFilterService::mutes($user->profile_id))->map(fn ($i) => (string) $i))
            ->toContain((string) $target->profile_id);

        Passport::actingAs($user, ['write']);
        $this->postJson("/api/v1/accounts/{$target->profile_id}/unmute")
            ->assertOk();

        expect(collect(UserFilterService::mutes($user->profile_id))->map(fn ($i) => (string) $i))
            ->not->toContain((string) $target->profile_id);
        expect(UserFilterService::muteCount($user->profile_id))->toBe(0);
    });
});

describe('modern App\Models\Profile control', function () {
    it('reports and unblocks a modern row identically', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();

        seedFilter($user->profile_id, $target->profile_id, 'block', Profile::class);

        $rel = RelationshipService::refresh($user->profile_id, $target->profile_id);
        expect($rel['blocking'])->toBeTrue();

        Passport::actingAs($user, ['write']);
        $this->postJson("/api/v1/accounts/{$target->profile_id}/unblock")
            ->assertOk();

        expect(UserFilter::where('user_id', $user->profile_id)
            ->where('filterable_id', $target->profile_id)
            ->where('filter_type', 'block')
            ->exists()
        )->toBeFalse();
    });
});

describe('legacy morph normalization migration', function () {
    it('normalizes legacy rows, drops colliding duplicates, and keeps distinct pairs', function () {
        $user = User::factory()->create();
        $user->refresh();
        $lone = User::factory()->create();
        $lone->refresh();
        $collide = User::factory()->create();
        $collide->refresh();
        $distinct = User::factory()->create();
        $distinct->refresh();

        // lone legacy row with no modern twin
        seedFilter($user->profile_id, $lone->profile_id, 'block', LEGACY_TYPE);

        // legacy row colliding with an existing modern row on the unique tuple
        $legacyCollision = seedFilter($user->profile_id, $collide->profile_id, 'block', LEGACY_TYPE);
        seedFilter($user->profile_id, $collide->profile_id, 'block', Profile::class);

        // distinct legacy row (mute) that must survive untouched in value
        seedFilter($user->profile_id, $distinct->profile_id, 'mute', LEGACY_TYPE);

        runMorphNormalization();

        // a second pass is a no-op
        runMorphNormalization();

        // no legacy alias remains
        expect(UserFilter::where('filterable_type', LEGACY_TYPE)->exists())->toBeFalse();

        // the colliding legacy row was removed, the modern one kept (single row)
        expect(UserFilter::where('user_id', $user->profile_id)
            ->where('filterable_id', $collide->profile_id)
            ->where('filter_type', 'block')
            ->count()
        )->toBe(1);
        expect(UserFilter::whereKey($legacyCollision->id)->exists())->toBeFalse();

        // the lone and distinct rows were normalized, not lost
        expect(UserFilter::where('filterable_id', $lone->profile_id)
            ->where('filterable_type', Profile::class)
            ->where('filter_type', 'block')
            ->exists()
        )->toBeTrue();
        expect(UserFilter::where('filterable_id', $distinct->profile_id)
            ->where('filterable_type', Profile::class)
            ->where('filter_type', 'mute')
            ->exists()
        )->toBeTrue();
    });
});
