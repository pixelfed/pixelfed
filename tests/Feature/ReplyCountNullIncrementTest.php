<?php

use App\Jobs\CommentPipeline\CommentPipeline;
use App\Jobs\StatusPipeline\StatusReplyPipeline;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| reply_count NULL increment - regression
|--------------------------------------------------------------------------
|
| reply_count is nullable with no default, and local status creation uses
| `new Status` without setting it, so freshly composed posts persist NULL.
| After the switch to an atomic increment, the emitted SQL is
| `reply_count = reply_count + 1`, and SQL NULL + 1 = NULL, so the counter
| never advanced and rendered as 0 forever. The pipelines must increment
| NULL-safely so a parent that starts at NULL reaches 1 on its first reply.
|
*/

beforeEach(function () {
    Redis::spy();
    Http::fake();

    config(['federation.activitypub.enabled' => false]);
});

/**
 * Force a persisted status back to a NULL reply_count, bypassing the factory
 * default of 0. This reproduces the real-world state of a post composed via
 * the `new Status` controller path, which never sets the column.
 */
function nullReplyCount(Status $status): void
{
    DB::table('statuses')->where('id', $status->id)->update(['reply_count' => null]);
}

it('increments a NULL reply_count to 1 on the first local reply (REGRESSION)', function () {
    $author = User::factory()->create();
    $author->refresh();
    $commenter = User::factory()->create();
    $commenter->refresh();

    $parent = Status::factory()->photo()->create(['profile_id' => $author->profile_id]);
    nullReplyCount($parent);

    $comment = Status::factory()->create([
        'profile_id' => $commenter->profile_id,
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $author->profile_id,
    ]);

    (new CommentPipeline($parent, $comment))->handle();

    expect((int) $parent->fresh()->reply_count)->toBe(1);
});

it('increments a NULL reply_count to 1 on the first remote reply (REGRESSION)', function () {
    $author = User::factory()->create();
    $author->refresh();
    $replier = User::factory()->create();
    $replier->refresh();

    $parent = Status::factory()->photo()->create(['profile_id' => $author->profile_id]);
    nullReplyCount($parent);

    $reply = Status::factory()->create([
        'profile_id' => $replier->profile_id,
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $author->profile_id,
    ]);

    (new StatusReplyPipeline($reply))->handle();

    expect((int) $parent->fresh()->reply_count)->toBe(1);
});

it('keeps counting on a NULL parent across successive replies', function () {
    $author = User::factory()->create();
    $author->refresh();
    $commenter = User::factory()->create();
    $commenter->refresh();

    $parent = Status::factory()->photo()->create(['profile_id' => $author->profile_id]);
    nullReplyCount($parent);

    foreach (range(1, 3) as $i) {
        $comment = Status::factory()->create([
            'profile_id' => $commenter->profile_id,
            'in_reply_to_id' => $parent->id,
            'in_reply_to_profile_id' => $author->profile_id,
        ]);

        (new CommentPipeline($parent, $comment))->handle();

        expect((int) $parent->fresh()->reply_count)->toBe($i);
    }
});

it('defaults reply_count to 0 for a status created with new Status', function () {
    $author = User::factory()->create();
    $author->refresh();

    $status = new Status;
    $status->profile_id = $author->profile_id;
    $status->type = 'text';
    $status->caption = 'hello';
    $status->scope = 'public';
    $status->visibility = 'public';
    $status->local = true;
    $status->rendered = '';
    $status->save();

    expect($status->fresh()->reply_count)->not->toBeNull();
    expect((int) $status->fresh()->reply_count)->toBe(0);
});
