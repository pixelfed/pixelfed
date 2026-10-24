<?php

use App\Http\Controllers\AdminController;
use App\Models\AccountInterstitial;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Autospam bulk approve must restore each status's own is_nsfw
|--------------------------------------------------------------------------
|
| The bulk approve-all / mark-all-not-spam loop captured the trigger appeal's
| decoded meta and applied its is_nsfw to every status, corrupting the content
| warning on posts whose own appeal had a different is_nsfw. Each status must
| be restored from its own appeal's meta snapshot.
|
*/

it('preserves each status content warning from its own appeal meta on bulk approve', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile_id;

    // An NSFW post and an SFW post, both flagged as autospam.
    $nsfwStatus = Status::factory()->create(['profile_id' => $pid, 'type' => 'photo', 'is_nsfw' => true]);
    $sfwStatus = Status::factory()->create(['profile_id' => $pid, 'type' => 'photo', 'is_nsfw' => false]);

    $nsfwAppeal = AccountInterstitial::createFromStatus($nsfwStatus, 'post.autospam', 'account.moderation.post.autospam');
    $sfwAppeal = AccountInterstitial::createFromStatus($sfwStatus, 'post.autospam', 'account.moderation.post.autospam');

    // Trigger the bulk action from the SFW appeal (meta.is_nsfw = false).
    (new AdminController)->reportsHandleSpamAction($sfwAppeal, 'mark-all-not-spam');

    // The NSFW post must keep its content warning, not inherit the trigger's false.
    expect((bool) $nsfwStatus->fresh()->is_nsfw)->toBeTrue();
    // The SFW post stays SFW.
    expect((bool) $sfwStatus->fresh()->is_nsfw)->toBeFalse();

    // Both appeals were handled.
    expect(AccountInterstitial::whereNull('appeal_handled_at')->whereUserId($user->id)->count())->toBe(0);
});
