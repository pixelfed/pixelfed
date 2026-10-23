<?php

use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Direct Message read endpoint
|--------------------------------------------------------------------------
|
| DirectMessageController@read previously fetched every matching row and
| saved each one in a loop. It now performs a single bulk update. These
| tests lock in the observable behaviour: matching messages are marked
| read and their ids are returned.
|
*/

function makeDm(int $toId, int $fromId, int $statusId): DirectMessage
{
    $dm = new DirectMessage;
    $dm->to_id = $toId;
    $dm->from_id = $fromId;
    $dm->status_id = $statusId;
    $dm->read_at = null;
    $dm->save();

    return $dm;
}

describe('POST /api/v1.1/direct/thread/read', function () {
    it('marks matching messages as read and returns their ids', function () {
        $recipient = User::factory()->create();
        $recipient->refresh();
        $sender = User::factory()->create();
        $sender->refresh();

        $dmOne = makeDm($recipient->profile_id, $sender->profile_id, 1000);
        $dmTwo = makeDm($recipient->profile_id, $sender->profile_id, 1001);

        Passport::actingAs($recipient, ['write']);

        $response = $this->postJson('/api/v1.1/direct/thread/read', [
            'pid' => $sender->profile_id,
            'sid' => 1000,
        ]);

        $response->assertOk();

        $returned = collect($response->json())->map(fn ($id) => (int) $id)->all();
        expect($returned)->toContain($dmOne->id)->toContain($dmTwo->id);

        expect(DirectMessage::find($dmOne->id)->read_at)->not->toBeNull();
        expect(DirectMessage::find($dmTwo->id)->read_at)->not->toBeNull();
    });

    it('does not mark messages below the given status id', function () {
        $recipient = User::factory()->create();
        $recipient->refresh();
        $sender = User::factory()->create();
        $sender->refresh();

        $older = makeDm($recipient->profile_id, $sender->profile_id, 500);
        $newer = makeDm($recipient->profile_id, $sender->profile_id, 900);

        Passport::actingAs($recipient, ['write']);

        $this->postJson('/api/v1.1/direct/thread/read', [
            'pid' => $sender->profile_id,
            'sid' => 900,
        ])->assertOk();

        expect(DirectMessage::find($older->id)->read_at)->toBeNull();
        expect(DirectMessage::find($newer->id)->read_at)->not->toBeNull();
    });

    it('does not mark another senders messages as read', function () {
        $recipient = User::factory()->create();
        $recipient->refresh();
        $sender = User::factory()->create();
        $sender->refresh();
        $other = User::factory()->create();
        $other->refresh();

        $fromSender = makeDm($recipient->profile_id, $sender->profile_id, 700);
        $fromOther = makeDm($recipient->profile_id, $other->profile_id, 700);

        Passport::actingAs($recipient, ['write']);

        $this->postJson('/api/v1.1/direct/thread/read', [
            'pid' => $sender->profile_id,
            'sid' => 700,
        ])->assertOk();

        expect(DirectMessage::find($fromSender->id)->read_at)->not->toBeNull();
        expect(DirectMessage::find($fromOther->id)->read_at)->toBeNull();
    });
});
