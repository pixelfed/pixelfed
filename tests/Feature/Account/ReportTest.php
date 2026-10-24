<?php

use App\Models\Report;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Report Tests
|--------------------------------------------------------------------------
*/

describe('report submission', function () {
    it('creates a report for a status', function () {
        $user = User::factory()->create();
        $user->refresh();
        $other = User::factory()->create();
        $other->refresh();
        $status = Status::factory()->create([
            'profile_id' => $other->profile_id,
            'type' => 'photo',
        ]);

        $this->actingAs($user)
            ->postJson('/i/report', [
                'report' => 'spam',
                'type' => 'post',
                'id' => $status->id,
                'msg' => 'This is spam content',
            ])
            ->assertOk();

        expect(Report::where('profile_id', $user->profile_id)->exists())->toBeTrue();
    });

    it('rejects report with invalid type', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->postJson('/i/report', [
                'report' => 'invalid_type',
                'type' => 'post',
                'id' => 1,
            ])
            ->assertStatus(400);
    });

    it('validates required fields', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->postJson('/i/report', [])
            ->assertUnprocessable();
    });

    it('requires authentication', function () {
        $this->postJson('/i/report', [
            'report' => 'spam',
            'type' => 'post',
            'id' => 1,
        ])->assertUnauthorized();
    });
});
