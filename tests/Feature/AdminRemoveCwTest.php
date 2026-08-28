<?php

use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Admin Remove CW - Regression Tests
|--------------------------------------------------------------------------
|
| Ensures that removing a content warning via the moderation action does
| not crash when no AccountInterstitial record exists (user-set CW).
|
*/

describe('admin remove content warning', function () {
    it('succeeds when no AccountInterstitial exists for the CW post', function () {
        $admin = User::factory()->admin()->create();
        $admin->refresh();

        $user = User::factory()->create();
        $user->refresh();

        $status = Status::factory()->nsfw()->create([
            'profile_id' => $user->profile->id,
            'uri' => null,
        ]);

        expect($status->is_nsfw)->toBeTrue();

        $this->actingAs($admin)
            ->postJson('/api/v2/moderator/action', [
                'action' => 'remcw',
                'item_id' => $status->id,
                'item_type' => 'status',
            ])
            ->assertOk();

        $status->refresh();
        expect((bool) $status->is_nsfw)->toBeFalse();
    });
});
