<?php

use App\Jobs\StatusPipeline\StatusRemoteUpdatePipeline;
use App\Models\Status;
use App\Models\User;
use App\Services\SanitizeService;
use App\Transformer\Api\StatusStatelessTransformer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Remote-update rendered sync
|--------------------------------------------------------------------------
|
| A remote Update carries new content. The pipeline updated caption but left
| the stored rendered HTML untouched, so transformers (which prefer rendered
| for remote statuses) kept serving the original Create-time HTML forever.
| rendered must be updated alongside caption so edits take effect.
|
*/

beforeEach(function () {
    Redis::spy();
});

function staleRenderedRemoteStatus(): Status
{
    $user = User::factory()->create();
    $user->refresh();

    $objectUrl = 'https://remote.example/users/bob/statuses/'.uniqid();

    return Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'text',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'uri' => $objectUrl,
        'object_url' => $objectUrl,
        'caption' => 'old text',
        'rendered' => '<p>old text</p>',
    ]);
}

function transformStaleStateless(Status $status): array
{
    $fractal = new Fractal\Manager;
    $fractal->setSerializer(new ArraySerializer);

    return $fractal->createData(
        new Fractal\Resource\Item($status, new StatusStatelessTransformer)
    )->toArray();
}

it('updates rendered on a remote edit so the transformer serves the edited html', function () {
    $status = staleRenderedRemoteStatus();

    $expected = app(SanitizeService::class)->html('<p>new text</p>');

    (new StatusRemoteUpdatePipeline([
        'id' => $status->object_url,
        'type' => 'Update',
        'content' => '<p>new text</p>',
    ]))->handle();

    $fresh = $status->fresh();

    expect($fresh->rendered)->toBe($expected);
    expect($fresh->rendered)->not->toBe('<p>old text</p>');
    expect($fresh->caption)->toBe('new text');

    $output = transformStaleStateless($fresh);

    expect($output['content'])->toBe($expected);
    expect($output['content'])->not->toBe('<p>old text</p>');
});
