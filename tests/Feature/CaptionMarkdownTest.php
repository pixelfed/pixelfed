<?php

use App\Models\Status;
use App\Models\User;
use App\Transformer\ActivityPub\Verb\Note;
use App\Transformer\Api\Mastodon\v1\StatusTransformer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use League\Fractal;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::spy();
    Queue::fake();
    Http::fake();
});

function markdownStatus(string $caption): Status
{
    $user = User::factory()->create();

    return Status::factory()->photo()->create([
        'profile_id' => $user->profile_id,
        'caption' => $caption,
    ]);
}

function markdownTransform(Status $status, Fractal\TransformerAbstract $transformer): array
{
    return (new Fractal\Manager)->createData(new Fractal\Resource\Item($status, $transformer))->toArray()['data'];
}

it('federates bold and italic as html while keeping the markdown caption', function () {
    $status = markdownStatus("**big** news\n*small* print");

    $note = markdownTransform($status, new Note);

    expect($note['content'])->toBe("<strong>big</strong> news<br />\n<em>small</em> print")
        ->and($status->fresh()->caption)->toBe("**big** news\n*small* print");
});

it('renders markdown in the mastodon api content', function () {
    $status = markdownStatus('**big** *small*');

    expect(markdownTransform($status, new StatusTransformer)['content'])->toBe('<strong>big</strong> <em>small</em>');
});

it('keeps strong and em from remote html with the restricted allow-list', function () {
    expect(Purify::clean('<p><strong>big</strong> <em>small</em> <del>x</del></p>'))
        ->toBe('<p><strong>big</strong> <em>small</em> x</p>');
});
