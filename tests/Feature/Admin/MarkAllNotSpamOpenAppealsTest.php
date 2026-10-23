<?php

use App\Http\Controllers\AdminController;
use App\Models\AccountInterstitial;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Admin "Mark All As Not Spam" only approves open appeals
|--------------------------------------------------------------------------
|
| The bulk mark-all-not-spam action was missing the whereNull('appeal_handled_at')
| filter that every sibling bulk action has. Approving a user's open appeals
| therefore re-opened previously-confirmed-spam interstitials: reset is_spam to
| false, re-stamped appeal_handled_at, and flipped the confirmed-spam status
| from unlisted back to public.
|
*/

beforeEach(function () {
    Redis::spy();
});

function autospamInterstitial(User $user, Status $status, bool $handled, bool $isSpam): AccountInterstitial
{
    $ai = new AccountInterstitial;
    $ai->user_id = $user->id;
    $ai->type = 'post.autospam';
    $ai->view = 'account.moderation.post.autospam';
    $ai->item_type = Status::class;
    $ai->item_id = $status->id;
    $ai->is_spam = $isSpam;
    $ai->appeal_handled_at = $handled ? now()->subDay() : null;
    $ai->meta = json_encode(['is_nsfw' => false]);
    $ai->save();

    return $ai;
}

it('does not re-open a previously handled confirmed-spam interstitial', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile_id;

    // A confirmed-spam post from a prior moderation round: handled, unlisted.
    $confirmed = Status::factory()->create([
        'profile_id' => $pid,
        'type' => 'text',
        'scope' => 'unlisted',
        'visibility' => 'unlisted',
    ]);
    $handledAppeal = autospamInterstitial($user, $confirmed, handled: true, isSpam: true);

    // A new, still-open appeal the admin actually wants to approve.
    $pending = Status::factory()->create([
        'profile_id' => $pid,
        'type' => 'text',
        'scope' => 'unlisted',
        'visibility' => 'unlisted',
    ]);
    $openAppeal = autospamInterstitial($user, $pending, handled: false, isSpam: true);

    (new AdminController)->reportsHandleSpamAction($openAppeal, 'mark-all-not-spam');

    // The previously-handled confirmed-spam record and its status are untouched.
    $handledAppeal->refresh();
    expect((bool) $handledAppeal->is_spam)->toBeTrue();
    expect($confirmed->fresh()->scope)->toBe('unlisted')
        ->and($confirmed->fresh()->visibility)->toBe('unlisted');
});

it('still approves the open appeals', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile_id;

    $pending = Status::factory()->create([
        'profile_id' => $pid,
        'type' => 'text',
        'scope' => 'unlisted',
        'visibility' => 'unlisted',
    ]);
    $openAppeal = autospamInterstitial($user, $pending, handled: false, isSpam: true);

    (new AdminController)->reportsHandleSpamAction($openAppeal, 'mark-all-not-spam');

    // The open appeal is resolved as not-spam and its post published.
    $openAppeal->refresh();
    expect((bool) $openAppeal->is_spam)->toBeFalse()
        ->and($openAppeal->appeal_handled_at)->not->toBeNull();
    expect($pending->fresh()->scope)->toBe('public')
        ->and($pending->fresh()->visibility)->toBe('public');
});
