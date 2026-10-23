<?php

use App\Models\Status;
use App\Models\User;
use App\Transformer\Api\StatusStatelessTransformer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| StatusStatelessTransformer must not embed a private reblog target
|--------------------------------------------------------------------------
|
| The stateless transformer fetched the reblogged status with publicOnly
| = false, so a public boost of a private (followers-only) status embedded
| the private original's full content in `reblog` — readable by anyone,
| including anonymous viewers. A stateless context can't verify the viewer,
| so only public reblog targets may be embedded.
|
*/

function transformStateless(Status $status): array
{
    $fractal = new Fractal\Manager;
    $fractal->setSerializer(new ArraySerializer);

    return $fractal->createData(
        new Fractal\Resource\Item($status, new StatusStatelessTransformer)
    )->toArray();
}

function boostOf(string $scope): array
{
    $author = User::factory()->create();
    $author->refresh();
    $sharer = User::factory()->create();
    $sharer->refresh();

    $original = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => $scope,
        'visibility' => $scope,
    ]);

    $boost = Status::factory()->create([
        'profile_id' => $sharer->profile_id,
        'type' => 'share',
        'scope' => 'public',
        'visibility' => 'public',
        'reblog_of_id' => $original->id,
    ]);

    return [$boost, $original];
}

it('does not embed a private reblog target', function () {
    [$boost] = boostOf('private');

    $res = transformStateless($boost);

    expect($res['reblog'])->toBeNull();
});

it('embeds a public reblog target', function () {
    [$boost, $original] = boostOf('public');

    $res = transformStateless($boost);

    expect($res['reblog'])->not->toBeNull();
    expect((string) $res['reblog']['id'])->toBe((string) $original->id);
});
