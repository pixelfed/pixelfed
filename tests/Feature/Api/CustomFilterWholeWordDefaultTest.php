<?php

use App\Models\CustomFilterKeyword;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| POST /api/v2/filters whole_word default
|--------------------------------------------------------------------------
|
| store() assigned whole_word via `(bool) $x ?? true`; the cast binds tighter
| than ??, so a missing whole_word key raised an undefined-key warning ->
| ErrorException -> 500. It must coalesce before casting and default to true.
|
*/

it('defaults whole_word to true when omitted from the keyword', function () {
    $user = User::factory()->create();
    $user->refresh();

    Passport::actingAs($user, ['read', 'write']);

    $response = $this->postJson('/api/v2/filters', [
        'title' => 'Spam filter',
        'context' => ['home'],
        'filter_action' => 'warn',
        'keywords_attributes' => [
            ['keyword' => 'book'],
        ],
    ]);

    $response->assertOk();
    expect($response->json('keywords')[0]['whole_word'])->toBeTrue();
    expect((bool) CustomFilterKeyword::where('keyword', 'book')->first()->whole_word)->toBeTrue();
});

it('honors an explicit whole_word=false', function () {
    $user = User::factory()->create();
    $user->refresh();

    Passport::actingAs($user, ['read', 'write']);

    $response = $this->postJson('/api/v2/filters', [
        'title' => 'Substring filter',
        'context' => ['home'],
        'filter_action' => 'warn',
        'keywords_attributes' => [
            ['keyword' => 'cat', 'whole_word' => false],
        ],
    ]);

    $response->assertOk();
    expect($response->json('keywords')[0]['whole_word'])->toBeFalse();
});

it('honors an explicit whole_word=true', function () {
    $user = User::factory()->create();
    $user->refresh();

    Passport::actingAs($user, ['read', 'write']);

    $response = $this->postJson('/api/v2/filters', [
        'title' => 'Word filter',
        'context' => ['home'],
        'filter_action' => 'warn',
        'keywords_attributes' => [
            ['keyword' => 'dog', 'whole_word' => true],
        ],
    ]);

    $response->assertOk();
    expect($response->json('keywords')[0]['whole_word'])->toBeTrue();
});
