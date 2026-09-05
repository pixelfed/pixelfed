<?php

namespace Tests\Feature;

use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\DirectMessage;
use App\Models\MediaTag;
use App\Models\Notification;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| StatusDelete cleanup
|--------------------------------------------------------------------------
|
| unlinkRemoveMedia() previously looped over each associated DirectMessage
| and MediaTag, running a per-row Notification lookup and delete. It now
| fetches the ids, resolves notifications in a single query, clears each
| notification (cache + redis) via cursor, then bulk deletes. These tests
| lock in the observable cleanup behaviour.
|
*/

class StatusDeleteCleanupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function deleting_a_status_removes_associated_dms_and_their_notifications()
    {
        $user = User::factory()->create();
        $user->refresh();

        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
        ]);

        $dm = new DirectMessage;
        $dm->to_id = $user->profile_id;
        $dm->from_id = $user->profile_id;
        $dm->status_id = $status->id;
        $dm->save();

        $notification = Notification::create([
            'profile_id' => $user->profile_id,
            'actor_id' => $user->profile_id,
            'action' => 'dm',
            'item_type' => DirectMessage::class,
            'item_id' => $dm->id,
        ]);

        (new StatusDelete($status))->handle();

        $this->assertNull(DirectMessage::find($dm->id));
        $this->assertNull(Notification::find($notification->id));
        $this->assertNull(Status::find($status->id));
    }

    #[Test]
    public function deleting_a_status_removes_associated_media_tags_and_their_notifications()
    {
        $user = User::factory()->create();
        $user->refresh();

        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
        ]);

        $tag = MediaTag::create([
            'status_id' => $status->id,
            'media_id' => 1,
            'profile_id' => $user->profile_id,
            'tagged_username' => 'someone',
        ]);

        $notification = Notification::create([
            'profile_id' => $user->profile_id,
            'actor_id' => $user->profile_id,
            'action' => 'tagged',
            'item_type' => MediaTag::class,
            'item_id' => $tag->id,
        ]);

        (new StatusDelete($status))->handle();

        $this->assertNull(MediaTag::find($tag->id));
        $this->assertNull(Notification::find($notification->id));
    }

    #[Test]
    public function deleting_a_status_without_dms_or_tags_still_deletes_the_status()
    {
        $user = User::factory()->create();
        $user->refresh();

        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
        ]);

        (new StatusDelete($status))->handle();

        $this->assertNull(Status::find($status->id));
    }
}
