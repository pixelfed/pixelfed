<?php

use App\Models\Like;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| admin:fixPostCounts
|--------------------------------------------------------------------------
|
| Resyncs a status's cached likes_count, reblogs_count and reply_count
| columns from source-of-truth tables (likes, reblog_of_id, in_reply_to_id).
|
*/

/**
 * Create a status owned by a fresh local user, with the given cached counts.
 */
function makeStatus(array $counts = []): Status
{
    $user = User::factory()->create();
    $user->refresh();

    return Status::factory()->photo()->create(array_merge([
        'profile_id' => $user->profile->id,
        'likes_count' => 0,
        'reblogs_count' => 0,
        'reply_count' => 0,
    ], $counts));
}

function addLikes(Status $status, int $n): void
{
    for ($i = 0; $i < $n; $i++) {
        $liker = User::factory()->create();
        $liker->refresh();
        Like::create([
            'profile_id' => $liker->profile->id,
            'status_id' => $status->id,
        ]);
    }
}

function addBoosts(Status $status, int $n): void
{
    for ($i = 0; $i < $n; $i++) {
        Status::factory()->create([
            'profile_id' => $status->profile_id,
            'reblog_of_id' => $status->id,
            'type' => 'share',
        ]);
    }
}

function addReplies(Status $status, int $n): void
{
    for ($i = 0; $i < $n; $i++) {
        Status::factory()->create([
            'profile_id' => $status->profile_id,
            'in_reply_to_id' => $status->id,
            'type' => 'reply',
        ]);
    }
}

it('resyncs all three counts from source-of-truth tables', function () {
    $status = makeStatus(['likes_count' => 99, 'reblogs_count' => 7, 'reply_count' => 0]);
    addLikes($status, 2);
    addBoosts($status, 0);
    addReplies($status, 1);

    $this->artisan('admin:fixPostCounts', ['id' => (string) $status->id])
        ->assertExitCode(0);

    $status->refresh();
    expect((int) $status->likes_count)->toBe(2);
    expect((int) $status->reblogs_count)->toBe(0);
    expect((int) $status->reply_count)->toBe(1);
});

it('does not change anything in dry-run mode', function () {
    $status = makeStatus(['likes_count' => 99]);
    addLikes($status, 2);

    $this->artisan('admin:fixPostCounts', ['id' => (string) $status->id, '--dry-run' => true])
        ->assertExitCode(0);

    expect((int) $status->fresh()->likes_count)->toBe(99);
});

it('leaves an already-correct status untouched', function () {
    $status = makeStatus(['likes_count' => 2, 'reblogs_count' => 0, 'reply_count' => 1]);
    addLikes($status, 2);
    addReplies($status, 1);

    $updatedAt = $status->fresh()->updated_at;

    $this->artisan('admin:fixPostCounts', ['id' => (string) $status->id])
        ->assertExitCode(0);

    // No drift -> no write -> updated_at unchanged.
    expect($status->fresh()->updated_at->eq($updatedAt))->toBeTrue();
});

it('only reports metrics that actually drifted in the summary', function () {
    // likes + boosts are wrong; comments is already correct.
    $status = makeStatus(['likes_count' => 7, 'reblogs_count' => 1, 'reply_count' => 3]);
    addLikes($status, 1);
    addBoosts($status, 0);
    addReplies($status, 3);

    $this->artisan('admin:fixPostCounts', ['id' => (string) $status->id])
        ->expectsOutputToContain('resynced: likes 7->1, boosts 1->0.')
        ->assertExitCode(0);
})->group('regression');

it('reports a drifted comments count in both the detected and resynced output', function () {
    // comments is wrong (cached 1, real 3).
    $status = makeStatus(['likes_count' => 7, 'reblogs_count' => 1, 'reply_count' => 1]);
    addLikes($status, 1);
    addReplies($status, 3);

    $this->artisan('admin:fixPostCounts', ['id' => (string) $status->id])
        ->expectsOutputToContain('comments:')
        ->expectsOutputToContain('comments 1->3')
        ->assertExitCode(0);

    expect((int) $status->fresh()->reply_count)->toBe(3);
})->group('regression');

it('displays a null reply_count as 0 rather than blank', function () {
    // Force reply_count to NULL at the DB level; only boosts will drift.
    $status = makeStatus(['reblogs_count' => 3]);
    DB::table('statuses')->where('id', $status->id)->update(['reply_count' => null]);

    // Boosts drift 3 -> 0; comments not drifted, so it must not print blank.
    $this->artisan('admin:fixPostCounts', ['id' => (string) $status->id])
        ->doesntExpectOutputToContain('comments=.')
        ->assertExitCode(0);
})->group('regression');

it('restricts to a single metric with --type', function () {
    $status = makeStatus(['likes_count' => 5, 'reblogs_count' => 5]);
    // Both are wrong (real 0), but only likes should be touched.
    $this->artisan('admin:fixPostCounts', ['id' => (string) $status->id, '--type' => 'likes'])
        ->assertExitCode(0);

    $status->refresh();
    expect((int) $status->likes_count)->toBe(0);
    // reblogs_count untouched because --type=likes.
    expect((int) $status->reblogs_count)->toBe(5);
});

it('rejects an invalid --type', function () {
    $status = makeStatus();
    $this->artisan('admin:fixPostCounts', ['id' => (string) $status->id, '--type' => 'bogus'])
        ->assertExitCode(1);
});

it('returns an error for an unknown status id', function () {
    $this->artisan('admin:fixPostCounts', ['id' => '999999999999'])
        ->assertExitCode(1);
});

it('requires a mode: id, --all, or --active', function () {
    $this->artisan('admin:fixPostCounts')
        ->assertExitCode(1);
});

it('rejects more than one mode at once', function () {
    $this->artisan('admin:fixPostCounts', ['--all' => true, '--active' => true])
        ->assertExitCode(1);
});

it('requires --scope when using --all', function () {
    $this->artisan('admin:fixPostCounts', ['--all' => true])
        ->assertExitCode(1);
});

it('resyncs drifted statuses in bulk --all mode', function () {
    $drifted = makeStatus(['likes_count' => 50]);
    addLikes($drifted, 1);

    $correct = makeStatus(['likes_count' => 0]);

    $this->artisan('admin:fixPostCounts', ['--all' => true, '--scope' => 'both', '--force' => true])
        ->assertExitCode(0);

    expect((int) $drifted->fresh()->likes_count)->toBe(1);
    expect((int) $correct->fresh()->likes_count)->toBe(0);
});
