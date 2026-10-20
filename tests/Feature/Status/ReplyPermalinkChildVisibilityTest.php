<?php

use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\FollowerService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function () {
    // Resolve views from the tree this test lives in, so the permalink page
    // under assertion is the one shipping alongside it.
    View::prependLocation(dirname(__DIR__, 3).'/resources/views');
});

/*
|--------------------------------------------------------------------------
| Private child replies must not leak on the reply permalink page
|--------------------------------------------------------------------------
|
| /p/{username}/{id} renders the thread context for a reply, including a
| preview of the first child reply. That preview must honour the same
| scope/follower visibility rule applied to the grandparent and parent
| blocks: a private-scope child's identity, permalink and text may only be
| shown to the child's author and their followers, never to guests or
| non-followers.
|
*/

function makeUser(bool $private = false): User
{
    $user = User::factory()->create();
    $user->refresh();
    if ($private) {
        $user->profile->update(['is_private' => true]);
    }

    return $user;
}

function buildThreadWithChild(string $childScope, string $marker, bool $privateAuthor): array
{
    $pOwner = makeUser();
    $p = Status::factory()->create([
        'profile_id' => $pOwner->profile_id,
        'scope' => 'public',
        'type' => 'photo',
    ]);

    $rOwner = makeUser();
    $r = Status::factory()->create([
        'profile_id' => $rOwner->profile_id,
        'scope' => 'public',
        'type' => 'reply',
        'in_reply_to_id' => $p->id,
        'in_reply_to_profile_id' => $pOwner->profile_id,
    ]);

    $cOwner = makeUser($privateAuthor);
    $child = Status::factory()->create([
        'profile_id' => $cOwner->profile_id,
        'scope' => $childScope,
        'visibility' => $childScope,
        'type' => 'reply',
        'in_reply_to_id' => $r->id,
        'in_reply_to_profile_id' => $rOwner->profile_id,
        'rendered' => '<p>'.$marker.'</p>',
    ]);

    return compact('rOwner', 'r', 'cOwner', 'child');
}

it('hides a private child reply from a guest', function () {
    $marker = 'secretguest'.uniqid();
    ['rOwner' => $rOwner, 'r' => $r, 'cOwner' => $cOwner, 'child' => $child] = buildThreadWithChild('private', $marker, true);

    $response = $this->get("/p/{$rOwner->profile->username}/{$r->id}");

    $response->assertOk();
    $response->assertDontSee($cOwner->profile->username);
    $response->assertDontSee("/p/{$cOwner->profile->username}/{$child->id}");
    $response->assertDontSee($marker);
});

it('hides a private child reply from a non-follower', function () {
    $marker = 'secretnonfollow'.uniqid();
    ['rOwner' => $rOwner, 'r' => $r, 'cOwner' => $cOwner, 'child' => $child] = buildThreadWithChild('private', $marker, true);

    $viewer = makeUser();

    $response = $this->actingAs($viewer)->get("/p/{$rOwner->profile->username}/{$r->id}?fs=1");

    $response->assertOk();
    $response->assertDontSee($cOwner->profile->username);
    $response->assertDontSee("/p/{$cOwner->profile->username}/{$child->id}");
    $response->assertDontSee($marker);
});

it('shows a private child reply to its author', function () {
    $marker = 'secretauthor'.uniqid();
    ['rOwner' => $rOwner, 'r' => $r, 'cOwner' => $cOwner] = buildThreadWithChild('private', $marker, true);

    $response = $this->actingAs($cOwner)->get("/p/{$rOwner->profile->username}/{$r->id}?fs=1");

    $response->assertOk();
    $response->assertSee($cOwner->profile->username);
    $response->assertSee($marker, false);
});

it('shows a private child reply to a follower', function () {
    $marker = 'secretfollower'.uniqid();
    ['rOwner' => $rOwner, 'r' => $r, 'cOwner' => $cOwner] = buildThreadWithChild('private', $marker, true);

    $viewer = makeUser();
    Follower::create([
        'profile_id' => $viewer->profile_id,
        'following_id' => $cOwner->profile_id,
        'local_profile' => true,
    ]);
    FollowerService::add($viewer->profile_id, $cOwner->profile_id);

    $response = $this->actingAs($viewer)->get("/p/{$rOwner->profile->username}/{$r->id}?fs=1");

    $response->assertOk();
    $response->assertSee($cOwner->profile->username);
    $response->assertSee($marker, false);
});

it('shows a public child reply to a guest', function () {
    $marker = 'publicchild'.uniqid();
    ['rOwner' => $rOwner, 'r' => $r, 'cOwner' => $cOwner] = buildThreadWithChild('public', $marker, false);

    $response = $this->get("/p/{$rOwner->profile->username}/{$r->id}");

    $response->assertOk();
    $response->assertSee($cOwner->profile->username);
    $response->assertSee($marker, false);
});
