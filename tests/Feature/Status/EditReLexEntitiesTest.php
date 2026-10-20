<?php

use App\Jobs\MentionPipeline\MentionPipeline;
use App\Jobs\StatusPipeline\StatusEntityLexer;
use App\Models\Hashtag;
use App\Models\Mention;
use App\Models\Profile;
use App\Models\Status;
use App\Models\StatusHashtag;
use App\Models\User;
use App\Services\Status\UpdateStatusService;
use App\Transformer\ActivityPub\Verb\UpdateNote;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\ArraySerializer;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

function editRelexMakeStatus(int $profileId, string $caption): Status
{
    $status = Status::factory()->create([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'caption' => $caption,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
    ]);

    (new StatusEntityLexer($status))->handle();

    return $status->fresh();
}

function editRelexTransform(Status $status): array
{
    $fractal = new Manager;
    $fractal->setSerializer(new ArraySerializer);

    return $fractal->createData(new Item($status, new UpdateNote))->toArray();
}

function editRelexTagNames(array $payload): array
{
    return collect($payload['object']['tag'])
        ->pluck('name')
        ->all();
}

it('re-lexes mentions and hashtags when the caption entities change', function () {
    $author = User::factory()->create();
    $author->refresh();

    $userA = User::factory()->create();
    $userA->refresh();
    $userB = User::factory()->create();
    $userB->refresh();
    $userC = User::factory()->create();
    $userC->refresh();

    $a = $author->profile->username;
    $usernameA = $userA->profile->username;
    $usernameB = $userB->profile->username;
    $usernameC = $userC->profile->username;

    $status = editRelexMakeStatus(
        $author->profile_id,
        "hey @{$usernameA} @{$usernameC} #tagOne"
    );

    $profileA = Profile::whereUsername(mb_strtolower($usernameA))->first();
    $profileB = Profile::whereUsername(mb_strtolower($usernameB))->first();
    $profileC = Profile::whereUsername(mb_strtolower($usernameC))->first();

    expect(Mention::whereStatusId($status->id)->whereProfileId($profileA->id)->count())->toBe(1);
    expect(Mention::whereStatusId($status->id)->whereProfileId($profileC->id)->count())->toBe(1);

    Queue::fake();

    UpdateStatusService::call(
        $status->fresh(),
        ['status' => "updated @{$usernameB} @{$usernameC} #tagTwo"]
    );

    // userB is now mentioned, userA was dropped
    expect(Mention::whereStatusId($status->id)->whereProfileId($profileB->id)->count())->toBe(1);
    expect(Mention::whereStatusId($status->id)->whereProfileId($profileA->id)->count())->toBe(0);
    expect(Mention::withTrashed()->whereStatusId($status->id)->whereProfileId($profileA->id)->count())->toBe(1);
    expect(Mention::whereStatusId($status->id)->whereProfileId($profileC->id)->count())->toBe(1);

    // hashtag rows swap from tagOne to tagTwo
    $tagOne = Hashtag::whereSlug('tagone')->first();
    $tagTwo = Hashtag::whereSlug('tagtwo')->first();
    expect($tagTwo)->not->toBeNull();
    expect(StatusHashtag::whereStatusId($status->id)->whereHashtagId($tagTwo->id)->count())->toBe(1);
    expect(StatusHashtag::whereStatusId($status->id)->whereHashtagId($tagOne->id)->count())->toBe(0);

    // only the newly added mention (userB) notifies; the unchanged userC does not
    Queue::assertPushed(MentionPipeline::class, 1);

    // federated payload reflects the new entity set
    $payload = editRelexTransform($status->fresh());
    $tagNames = editRelexTagNames($payload);

    expect($tagNames)->toContain('#tagTwo');
    expect($tagNames)->not->toContain('#tagOne');

    $mentionHrefs = collect($payload['object']['tag'])
        ->where('type', 'Mention')
        ->pluck('href')
        ->all();
    expect($mentionHrefs)->toContain($profileB->permalink());
    expect($mentionHrefs)->not->toContain($profileA->permalink());

    $cc = $status->fresh()->scopeToAudience('cc');
    expect($cc)->toContain($profileB->permalink());
    expect($cc)->not->toContain($profileA->permalink());
});

it('leaves entity rows untouched when only prose changes', function () {
    $author = User::factory()->create();
    $author->refresh();

    $userA = User::factory()->create();
    $userA->refresh();
    $usernameA = $userA->profile->username;

    $status = editRelexMakeStatus(
        $author->profile_id,
        "hello @{$usernameA} #tagOne"
    );

    $mentionIds = Mention::whereStatusId($status->id)->pluck('id')->sort()->values()->all();
    $hashtagRowIds = StatusHashtag::whereStatusId($status->id)->pluck('id')->sort()->values()->all();

    Queue::fake();

    UpdateStatusService::call(
        $status->fresh(),
        ['status' => "totally different prose @{$usernameA} #tagOne still here"]
    );

    expect(Mention::whereStatusId($status->id)->pluck('id')->sort()->values()->all())->toBe($mentionIds);
    expect(StatusHashtag::whereStatusId($status->id)->pluck('id')->sort()->values()->all())->toBe($hashtagRowIds);

    Queue::assertNotPushed(MentionPipeline::class);
});
