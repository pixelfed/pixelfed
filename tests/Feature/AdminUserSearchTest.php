<?php

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function ausUser(string $username, array $profile = [], array $attrs = []): User
{
    $user = User::factory()->create(['username' => $username]);
    $user->refresh();

    if ($profile) {
        Profile::whereKey($user->profile_id)->update($profile);
    }

    if ($attrs) {
        User::whereKey($user->id)->update($attrs);
    }

    return $user->refresh();
}

function ausSearch($test, string $q, bool $trashed = false): array
{
    $admin = User::where('username', 'searchadmin')->first()
        ?? User::factory()->admin()->create(['username' => 'searchadmin']);
    $admin->refresh();

    $query = ['a' => 'search', 'q' => $q];
    if ($trashed) {
        $query['trashed'] = '1';
    }

    $names = null;

    $test->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/i/admin/users/list?'.http_build_query($query))
        ->assertOk()
        ->assertViewHas('users', function ($users) use (&$names) {
            $names = $users->pluck('username')->reject(fn ($u) => $u === 'searchadmin')->sort()->values()->all();

            return true;
        });

    return $names;
}

it('matches bios containing the term when there is no wildcard', function () {
    ausUser('spammerone', ['bio' => 'Cheap followers, DM me on t.me/cheapfollows']);
    ausUser('spammertwo', ['bio' => 'T.ME/casino deals']);
    ausUser('photographer', ['bio' => 'Landscape photos from Alberta']);

    expect(ausSearch($this, 'bio:t.me'))->toBe(['spammerone', 'spammertwo']);
});

it('anchors bio patterns that use a wildcard', function () {
    ausUser('starts', ['bio' => 'join t.me/deals']);
    ausUser('middle', ['bio' => 'please join t.me/deals']);

    expect(ausSearch($this, 'bio:join*'))->toBe(['starts']);
});

it('treats percent and underscore as literal characters', function () {
    ausUser('underscore', ['bio' => 'tg: t.me/cheap_deals']);
    ausUser('lookalike', ['bio' => 'tg: t.me/cheapXdeals']);

    expect(ausSearch($this, 'bio:cheap_deals'))->toBe(['underscore']);
    expect(ausSearch($this, 'bio:100%'))->toBe([]);
});

it('matches websites exactly when there is no wildcard', function () {
    ausUser('exactsite', ['website' => 'https://t.me/foo']);
    ausUser('longersite', ['website' => 'https://t.me/foobar']);

    expect(ausSearch($this, 'website:https://t.me/foo'))->toBe(['exactsite']);
});

it('matches websites with wildcards', function () {
    ausUser('sitea', ['website' => 'https://t.me/foo']);
    ausUser('siteb', ['website' => 'http://t.me/bar']);
    ausUser('sitec', ['website' => 'https://pixelfed.org']);

    expect(ausSearch($this, 'website:*t.me*'))->toBe(['sitea', 'siteb']);
});

it('still searches usernames by default', function () {
    ausUser('casinobot', ['bio' => 'nothing here']);
    ausUser('photographer', ['bio' => 'casino']);

    expect(ausSearch($this, 'casino'))->toBe(['casinobot']);
});

it('keeps the search applied when showing deleted accounts', function () {
    ausUser('deletedspam', ['bio' => 't.me/spam'], ['status' => 'deleted']);
    ausUser('deletedother', ['bio' => 'just photos'], ['delete_after' => now()->addMonth()]);

    expect(ausSearch($this, 'bio:t.me', true))->toBe(['deletedspam']);
});
