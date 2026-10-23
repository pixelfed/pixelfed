<?php

use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\View;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    // Resolve views from the tree this test lives in, so the permalink page
    // under assertion is the one shipping alongside it.
    View::prependLocation(dirname(__DIR__, 3).'/resources/views');
});

/*
|--------------------------------------------------------------------------
| Reply permalink must not 500 when the parent status is gone
|--------------------------------------------------------------------------
|
| /p/{username}/{id} renders the thread context for a reply, which includes
| the parent and grandparent of the status. When the parent has been deleted
| (soft or force), the page must return a clean 404 rather than crashing with
| a 500 on a null parent dereference.
|
*/

function pdMakeUser(): User
{
    $user = User::factory()->create();
    $user->refresh();

    return $user;
}

function buildReplyWithParent(): array
{
    $parentOwner = pdMakeUser();
    $parent = Status::factory()->create([
        'profile_id' => $parentOwner->profile_id,
        'scope' => 'public',
        'type' => 'photo',
    ]);

    $replyOwner = pdMakeUser();
    $reply = Status::factory()->create([
        'profile_id' => $replyOwner->profile_id,
        'scope' => 'public',
        'type' => 'reply',
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $parentOwner->profile_id,
    ]);

    return compact('replyOwner', 'reply', 'parent');
}

it('returns 200 when the parent is alive', function () {
    ['replyOwner' => $replyOwner, 'reply' => $reply] = buildReplyWithParent();

    $response = $this->get("/p/{$replyOwner->profile->username}/{$reply->id}");

    $response->assertOk();
});

it('returns 404 (not 500) when the parent is soft-deleted', function () {
    ['replyOwner' => $replyOwner, 'reply' => $reply, 'parent' => $parent] = buildReplyWithParent();

    $parent->delete();

    $response = $this->get("/p/{$replyOwner->profile->username}/{$reply->id}");

    $response->assertNotFound();
});

it('returns 404 (not 500) when the parent is force-deleted', function () {
    ['replyOwner' => $replyOwner, 'reply' => $reply, 'parent' => $parent] = buildReplyWithParent();

    $parent->forceDelete();

    $response = $this->get("/p/{$replyOwner->profile->username}/{$reply->id}");

    $response->assertNotFound();
});
