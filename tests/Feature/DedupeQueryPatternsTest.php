<?php

use App\Models\CustomEmoji;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserFilter;
use App\Services\UserFilterService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Extracted query patterns
|--------------------------------------------------------------------------
|
| Covers UserFilterService::searchExcludedProfileIds (blocked-by ids + self,
| previously inlined in three search endpoints) and the
| CustomEmoji::duplicateShortcodes scope (previously three copies of
| groupBy('shortcode')->havingRaw('count(*) > 1')).
|
*/

describe('UserFilterService::searchExcludedProfileIds', function () {
    it('returns ids of profiles that blocked the user plus the users own id', function () {
        $user = User::factory()->create();
        $user->refresh();
        $blocker = User::factory()->create();
        $blocker->refresh();

        // $blocker has blocked $user: user_id = blocker, filterable_id = user.
        $filter = new UserFilter;
        $filter->user_id = $blocker->profile_id;
        $filter->filterable_id = $user->profile_id;
        $filter->filterable_type = Profile::class;
        $filter->filter_type = 'block';
        $filter->save();

        $excluded = UserFilterService::searchExcludedProfileIds($user->profile_id);

        expect($excluded->all())
            ->toContain($blocker->profile_id)
            ->toContain($user->profile_id);
    });

    it('returns only the users own id when nobody has blocked them', function () {
        $user = User::factory()->create();
        $user->refresh();

        $excluded = UserFilterService::searchExcludedProfileIds($user->profile_id);

        expect($excluded->all())->toBe([$user->profile_id]);
    });
});

describe('CustomEmoji::duplicateShortcodes', function () {
    it('only matches shortcodes that appear on more than one row', function () {
        // Two rows share :dupe: (different domains satisfy the unique index).
        CustomEmoji::create(['shortcode' => ':dupe:', 'domain' => 'a.example']);
        CustomEmoji::create(['shortcode' => ':dupe:', 'domain' => 'b.example']);
        // Unique shortcode, should be excluded.
        CustomEmoji::create(['shortcode' => ':unique:', 'domain' => 'a.example']);

        $shortcodes = CustomEmoji::duplicateShortcodes()->pluck('shortcode')->all();

        expect($shortcodes)->toContain(':dupe:')->not->toContain(':unique:');
        expect(CustomEmoji::duplicateShortcodes()->count())->toBe(1);
    });
});
