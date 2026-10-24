<?php

use App\Models\Status;
use App\Models\User;
use App\Util\ActivityPub\Inbox\InboxHelpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Story interaction dedup must query the stored (suffix-stripped) form
|--------------------------------------------------------------------------
|
| handleStoryInteraction stored object_url/uri/url with the trailing
| "/activity" stripped, but its dedup guard queried the raw wire id (with
| "/activity"), so a redelivery never matched and hit the unique index. The
| lookup must use the same stripped value that is stored.
|
*/

// Concrete host for the trait-provided helper.
function stripSuffix(string $url): string
{
    return (new class
    {
        use InboxHelpers;
    })->stripActivitySuffix($url);
}

it('dedups a redelivered story interaction by the stored stripped url', function () {
    $user = User::factory()->create();
    $user->refresh();

    $wireId = 'https://remote.example/users/alice/statuses/123/activity';
    $stored = stripSuffix($wireId);

    expect($stored)->toBe('https://remote.example/users/alice/statuses/123');

    // A prior delivery persisted the status under the stripped canonical form.
    Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'story:reply',
        'url' => $stored,
        'uri' => $stored,
        'object_url' => $stored,
        'scope' => 'direct',
        'visibility' => 'direct',
    ]);

    // The fixed dedup query (stripped value) must find the existing row.
    expect(Status::whereObjectUrl($stored)->exists())->toBeTrue();

    // The old buggy query (raw wire id with /activity) would have missed it.
    expect(Status::whereObjectUrl($wireId)->exists())->toBeFalse();
});
