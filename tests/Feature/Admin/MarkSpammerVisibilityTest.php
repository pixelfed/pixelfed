<?php

use App\Http\Controllers\AdminController;
use App\Models\AccountInterstitial;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Admin "Mark as spammer" must not widen post visibility
|--------------------------------------------------------------------------
|
| mark-spammer is a restrictive action (it unlists/CWs the profile). It used
| to rewrite every status to scope/visibility public, which leaked the user's
| followers-only and direct posts to anonymous web and API endpoints. It must
| only tag posts NSFW and leave scope/visibility untouched.
|
*/

function markSpammerAppeal(User $spammer, Status $trigger): AccountInterstitial
{
    $ai = new AccountInterstitial;
    $ai->user_id = $spammer->id;
    $ai->type = 'post.autospam';
    $ai->view = 'moderation.autospam';
    $ai->item_type = Status::class;
    $ai->item_id = $trigger->id;
    $ai->meta = json_encode(['is_nsfw' => false]);
    $ai->save();

    return $ai;
}

function callMarkSpammer(User $admin, int $appealId): void
{
    $request = Request::create('/i/admin/reports/autospam/'.$appealId, 'POST', [
        'action' => 'mark-spammer',
    ]);
    $request->setUserResolver(fn () => $admin);

    (new AdminController)->updateSpam($request, $appealId);
}

it('does not promote private or direct posts to public when marking a spammer', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $admin->refresh();

    $spammer = User::factory()->create();
    $spammer->refresh();
    $pid = $spammer->profile_id;

    $public = Status::factory()->create(['profile_id' => $pid, 'scope' => 'public', 'visibility' => 'public']);
    $private = Status::factory()->create(['profile_id' => $pid, 'scope' => 'private', 'visibility' => 'private']);
    $direct = Status::factory()->create(['profile_id' => $pid, 'scope' => 'direct', 'visibility' => 'direct']);

    $appeal = markSpammerAppeal($spammer, $public);

    callMarkSpammer($admin, $appeal->id);

    // Scope/visibility must be preserved exactly.
    expect($private->fresh()->scope)->toBe('private')
        ->and($private->fresh()->visibility)->toBe('private')
        ->and($direct->fresh()->scope)->toBe('direct')
        ->and($direct->fresh()->visibility)->toBe('direct')
        ->and($public->fresh()->scope)->toBe('public')
        ->and($public->fresh()->visibility)->toBe('public');
});

it('still tags the spammer posts nsfw and constrains the profile', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $admin->refresh();

    $spammer = User::factory()->create();
    $spammer->refresh();
    $pid = $spammer->profile_id;

    $public = Status::factory()->create(['profile_id' => $pid, 'scope' => 'public', 'visibility' => 'public']);
    $private = Status::factory()->create(['profile_id' => $pid, 'scope' => 'private', 'visibility' => 'private']);

    $appeal = markSpammerAppeal($spammer, $public);

    callMarkSpammer($admin, $appeal->id);

    // The restrictive intent still holds: posts tagged NSFW, profile locked down.
    expect((bool) $public->fresh()->is_nsfw)->toBeTrue()
        ->and((bool) $private->fresh()->is_nsfw)->toBeTrue();

    $profile = $spammer->profile->fresh();

    expect((bool) $profile->unlisted)->toBeTrue()
        ->and((bool) $profile->cw)->toBeTrue()
        ->and((bool) $profile->no_autolink)->toBeTrue();

    // The appeal is resolved as spam.
    expect((bool) $appeal->fresh()->is_spam)->toBeTrue()
        ->and($appeal->fresh()->appeal_handled_at)->not->toBeNull();
});
