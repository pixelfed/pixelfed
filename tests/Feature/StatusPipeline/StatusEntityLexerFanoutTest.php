<?php

use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Jobs\StatusPipeline\StatusEntityLexer;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| StatusEntityLexer fanout is independent of no_autolink
|--------------------------------------------------------------------------
|
| The `no_autolink` profile flag only disables turning mentions/hashtags/urls
| into HTML. It must NOT suppress post delivery (home feed fanout, public
| timeline, federation). The fanout must run regardless of the flag.
|
*/

beforeEach(function () {
    Queue::fake();
    config(['exp.cached_home_timeline' => true]);
});

function runLexerForProfileFlag(bool $noAutolink): Status
{
    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;
    $profile->no_autolink = $noAutolink;
    $profile->save();

    $status = Status::factory()->create([
        'profile_id' => $profile->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'in_reply_to_id' => null,
    ]);

    (new StatusEntityLexer($status))->handle();

    return $status;
}

it('fans out to the home feed when no_autolink is disabled', function () {
    runLexerForProfileFlag(false);

    Queue::assertPushed(FeedInsertPipeline::class);
});

it('still fans out to the home feed when no_autolink is enabled', function () {
    runLexerForProfileFlag(true);

    Queue::assertPushed(FeedInsertPipeline::class);
});
