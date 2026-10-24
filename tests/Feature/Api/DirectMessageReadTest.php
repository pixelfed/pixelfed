<?php

use App\Models\DmConversationParticipant;
use App\Models\DmMessage;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\DirectMessageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Direct Message read endpoint
|--------------------------------------------------------------------------
|
| DirectMessageController@read is the legacy way to mark a thread read. It
| is addressed by the other person's profile id and a message id, and
| returns the ids it marked. Read state now lives on the conversation
| participant, so these tests lock in the observable behaviour: messages
| from that sender at or after the given id are reported and the thread is
| read up to the newest of them.
|
*/

beforeEach(function () {
    Redis::spy();
    Queue::fake();

    $this->withoutMiddleware(ThrottleRequests::class);
    config(['snowflake.datacenter_id' => 1, 'snowflake.worker_id' => 1]);
});

function readTestUser(): User
{
    $user = User::factory()->create(['created_at' => now()->subYear()]);
    $user->refresh();

    UserSetting::updateOrCreate(['user_id' => $user->id], ['public_dm' => true]);

    return $user;
}

function readTestSend(User $from, User $to, string $text): DmMessage
{
    $service = app(DirectMessageService::class);
    $sender = Profile::findOrFail($from->profile_id);

    return $service->sendMessage(
        $service->findOrCreateDm($sender, Profile::findOrFail($to->profile_id)),
        $sender,
        ['body' => $text]
    );
}

describe('POST /api/v1.1/direct/thread/read', function () {
    it('marks matching messages as read and returns their ids', function () {
        $recipient = readTestUser();
        $sender = readTestUser();

        $one = readTestSend($sender, $recipient, 'one');
        $two = readTestSend($sender, $recipient, 'two');

        Passport::actingAs($recipient, ['write']);

        $response = $this->postJson('/api/v1.1/direct/thread/read', [
            'pid' => $sender->profile_id,
            'sid' => $one->id,
        ]);

        $response->assertOk();

        $returned = collect($response->json())->map(fn ($id) => (int) $id)->all();
        expect($returned)->toContain($one->id)->toContain($two->id);

        $state = DmConversationParticipant::where('profile_id', $recipient->profile_id)->first();

        expect($state->last_read_message_id)->toBe($two->id)
            ->and($state->unread_count)->toBe(0);
    });

    it('does not report messages below the given id', function () {
        $recipient = readTestUser();
        $sender = readTestUser();

        $older = readTestSend($sender, $recipient, 'older');
        $newer = readTestSend($sender, $recipient, 'newer');

        Passport::actingAs($recipient, ['write']);

        $returned = collect($this->postJson('/api/v1.1/direct/thread/read', [
            'pid' => $sender->profile_id,
            'sid' => $newer->id,
        ])->assertOk()->json())->map(fn ($id) => (int) $id)->all();

        expect($returned)->toBe([$newer->id]);
    });

    it('does not mark another senders messages as read', function () {
        $recipient = readTestUser();
        $sender = readTestUser();
        $other = readTestUser();

        $fromSender = readTestSend($sender, $recipient, 'hi');
        readTestSend($other, $recipient, 'hey');

        Passport::actingAs($recipient, ['write']);

        $this->postJson('/api/v1.1/direct/thread/read', [
            'pid' => $sender->profile_id,
            'sid' => $fromSender->id,
        ])->assertOk();

        $states = DmConversationParticipant::where('profile_id', $recipient->profile_id)->get();

        expect($states->firstWhere('last_read_message_id', $fromSender->id))->not->toBeNull()
            ->and($states->sum('unread_count'))->toBe(1);
    });
});
