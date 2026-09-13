<?php

use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Models\DirectMessage;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| DirectMessageController@delete media cleanup
|--------------------------------------------------------------------------
|
| Deleting a DM photo must clean up its Media row + on-disk file and refund the
| sender's storage quota, regardless of whether the recipient is local or
| remote. The remote branch only federates a Delete activity and the local
| branch's async StatusDelete races the controller's forceDeleteQuietly(), so
| the controller dispatches MediaDeletePipeline itself. Without this, the Media
| row is orphaned (status hard-deleted, status_id dangling) and leaks quota.
|
*/

/**
 * Create a DM photo (status + media + direct_message row) from $sender to
 * $recipientProfileId, returning the media row.
 */
function makeDmPhoto(User $sender, int $recipientProfileId): Media
{
    $status = Status::factory()->create([
        'profile_id' => $sender->profile_id,
        'type' => 'photo',
        'scope' => 'direct',
        'visibility' => 'direct',
    ]);

    $media = Media::create([
        'status_id' => $status->id,
        'profile_id' => $sender->profile_id,
        'user_id' => $sender->id,
        'media_path' => 'public/m/_v2/1/dm-'.$status->id.'.jpeg',
        'mime' => 'image/jpeg',
        'size' => 250000,
        'order' => 1,
    ]);

    $dm = new DirectMessage;
    $dm->to_id = $recipientProfileId;
    $dm->from_id = $sender->profile_id;
    $dm->status_id = $status->id;
    $dm->type = 'photo';
    $dm->save();

    return $media;
}

it('cleans up DM media when the recipient is local', function () {
    Bus::fake();

    $sender = User::factory()->create();
    $sender->refresh();
    $recipient = User::factory()->create();
    $recipient->refresh();

    $media = makeDmPhoto($sender, $recipient->profile_id);
    $statusId = $media->status_id;

    Passport::actingAs($sender, ['write']);

    $this->deleteJson('/api/v1.1/direct/thread/message', ['id' => $statusId])
        ->assertOk();

    // The media is detached from the (hard-deleted) status and queued for purge.
    expect(Media::whereId($media->id)->first()->status_id)->toBeNull();
    Bus::assertDispatched(MediaDeletePipeline::class, function ($job) use ($media) {
        return $job->uniqueId() === 'media:purge-job:id-'.$media->id;
    });

    expect(Status::whereId($statusId)->exists())->toBeFalse();
});

it('cleans up DM media when the recipient is remote', function () {
    Bus::fake();

    $sender = User::factory()->create();
    $sender->refresh();

    // Remote recipient: user_id null + domain set -> AccountService local=false.
    $remote = Profile::factory()->remote()->create();

    $media = makeDmPhoto($sender, $remote->id);
    $statusId = $media->status_id;

    Passport::actingAs($sender, ['write']);

    $this->deleteJson('/api/v1.1/direct/thread/message', ['id' => $statusId])
        ->assertOk();

    // Regression: the remote branch used to only federate a Delete and never
    // reach MediaDeletePipeline, orphaning the media row and leaking quota.
    expect(Media::whereId($media->id)->first()->status_id)->toBeNull();
    Bus::assertDispatched(MediaDeletePipeline::class, function ($job) use ($media) {
        return $job->uniqueId() === 'media:purge-job:id-'.$media->id;
    });

    expect(Status::whereId($statusId)->exists())->toBeFalse();
});
