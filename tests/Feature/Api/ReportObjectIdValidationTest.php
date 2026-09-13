<?php

use App\Models\Report;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| POST /api/v1.1/report object_id validation
|--------------------------------------------------------------------------
|
| object_id must be a positive integer. Without an integer constraint, an array
| object_id[]=1 passed validation and Status/Profile/Story::find([...]) returned
| an Eloquent Collection, whose __get('profile_id') throws -> HTTP 500 leaking
| an internal property name. Array/invalid input must instead be rejected with
| the endpoint's existing 400 ERROR_INVALID_PARAMS, matching the web
| ReportController which validates id as integer|min:1.
|
*/

it('rejects an array object_id with 400 ERROR_INVALID_PARAMS instead of 500', function () {
    $reporter = User::factory()->create();
    $reporter->refresh();
    $author = User::factory()->create();
    $author->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
    ]);

    Passport::actingAs($reporter, ['write']);

    $this->postJson('/api/v1.1/report', [
        'report_type' => 'spam',
        'object_id' => [$status->id], // array input that used to reach Collection::__get
        'object_type' => 'post',
    ])
        ->assertStatus(400)
        ->assertJsonPath('error_code', 'ERROR_INVALID_PARAMS');

    expect(Report::where('user_id', $reporter->id)->count())->toBe(0);
});

it('rejects a non-positive object_id', function () {
    $reporter = User::factory()->create();
    $reporter->refresh();

    Passport::actingAs($reporter, ['write']);

    $this->postJson('/api/v1.1/report', [
        'report_type' => 'spam',
        'object_id' => 0,
        'object_type' => 'post',
    ])
        ->assertStatus(400)
        ->assertJsonPath('error_code', 'ERROR_INVALID_PARAMS');
});

it('rejects a non-numeric object_id', function () {
    $reporter = User::factory()->create();
    $reporter->refresh();

    Passport::actingAs($reporter, ['write']);

    $this->postJson('/api/v1.1/report', [
        'report_type' => 'spam',
        'object_id' => 'abc',
        'object_type' => 'post',
    ])
        ->assertStatus(400)
        ->assertJsonPath('error_code', 'ERROR_INVALID_PARAMS');
});

it('accepts a valid scalar object_id and files the report', function () {
    $reporter = User::factory()->create();
    $reporter->refresh();
    $author = User::factory()->create();
    $author->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
    ]);

    Passport::actingAs($reporter, ['write']);

    $this->postJson('/api/v1.1/report', [
        'report_type' => 'spam',
        'object_id' => $status->id,
        'object_type' => 'post',
    ])
        ->assertOk()
        ->assertJsonPath('msg', 'Successfully sent report');

    expect(
        Report::where('user_id', $reporter->id)
            ->where('object_id', $status->id)
            ->where('object_type', Status::class)
            ->exists()
    )->toBeTrue();
});
