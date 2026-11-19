<?php

use App\Models\User;
use App\Services\CollectionService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->refresh();
});

it('shows the collections setting on the privacy page', function () {
    $this->actingAs($this->user)
        ->get('/settings/privacy')
        ->assertOk()
        ->assertSee('Who can add your posts to collections')
        ->assertSee('name="can_collect"', false);
});

it('saves the collections setting from the privacy page', function () {
    expect(CollectionService::collectPolicy($this->user->profile_id))->toBe('everyone');

    $this->actingAs($this->user->fresh())
        ->post('/settings/privacy', ['can_collect' => 'followers'])
        ->assertRedirect(route('settings.privacy'));

    expect($this->user->settings()->first()->can_collect)->toBe('followers')
        ->and(CollectionService::collectPolicy($this->user->profile_id))->toBe('followers');

    $page = $this->actingAs($this->user->fresh())->get('/settings/privacy')->getContent();

    expect($page)->toMatch('/name="can_collect".*?value="followers"\s+selected/s');
});

it('ignores values that are not a collections policy', function () {
    $this->actingAs($this->user->fresh())
        ->post('/settings/privacy', ['can_collect' => 'nobody'])
        ->assertRedirect();

    $this->actingAs($this->user->fresh())
        ->post('/settings/privacy', ['can_collect' => 'anyone'])
        ->assertRedirect();

    expect($this->user->settings()->first()->can_collect)->toBe('nobody');
});

it('matches what the API reports', function () {
    $this->actingAs($this->user->fresh())
        ->post('/settings/privacy', ['can_collect' => 'nobody'])
        ->assertRedirect();

    $this->actingAs($this->user->fresh(), 'api')
        ->getJson('/api/v1.2/collections/settings')
        ->assertOk()
        ->assertJsonPath('can_collect', 'nobody');
});
