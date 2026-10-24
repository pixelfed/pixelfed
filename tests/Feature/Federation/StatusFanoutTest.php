<?php

use App\Jobs\Federation\DeliverActivityChunk;
use App\Jobs\Federation\DeliverStatusDeleteActivity;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\ActivityPubFanoutService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

function fanoutLocalProfile(): Profile
{
    $user = User::factory()->create();
    $user->refresh();

    return $user->profile;
}

function fanoutInProduction(callable $fn): mixed
{
    $app = app();
    $previous = $app['env'];
    $app['env'] = 'production';

    try {
        return $fn();
    } finally {
        $app['env'] = $previous;
    }
}

it('splits the audience into chunks and tracks them per status', function () {
    Queue::fake();
    config(['federation.activitypub.delivery.chunk_size' => 2]);

    $profile = fanoutLocalProfile();

    $queued = ActivityPubFanoutService::dispatch(
        $profile,
        ['type' => 'Create'],
        [
            'https://a.example/inbox',
            'https://b.example/inbox',
            'https://b.example/inbox',
            'https://c.example/inbox',
            null,
            '',
            'https://d.example/inbox',
            'https://e.example/inbox',
        ],
        false,
        123
    );

    expect($queued)->toBe(3);
    expect(ActivityPubFanoutService::pending(123))->toBe(3);

    Queue::assertPushed(DeliverActivityChunk::class, 3);
    Queue::assertPushedOn(
        ActivityPubFanoutService::queue(),
        DeliverActivityChunk::class,
        fn (DeliverActivityChunk $job) => $job->statusId() === 123 && count($job->inboxes()) <= 2
    );
});

it('does not track a fanout without a status', function () {
    Queue::fake();

    $queued = ActivityPubFanoutService::dispatch(
        fanoutLocalProfile(),
        ['type' => 'Delete'],
        ['https://a.example/inbox']
    );

    expect($queued)->toBe(1);

    Queue::assertPushed(
        DeliverActivityChunk::class,
        fn (DeliverActivityChunk $job) => $job->statusId() === null
    );
});

it('settles pending chunks down to zero', function () {
    Queue::fake();
    config(['federation.activitypub.delivery.chunk_size' => 1]);

    ActivityPubFanoutService::dispatch(
        fanoutLocalProfile(),
        ['type' => 'Create'],
        ['https://a.example/inbox', 'https://b.example/inbox'],
        false,
        456
    );

    ActivityPubFanoutService::settle(456);
    expect(ActivityPubFanoutService::pending(456))->toBe(1);

    ActivityPubFanoutService::settle(456);
    ActivityPubFanoutService::settle(456);
    expect(ActivityPubFanoutService::pending(456))->toBe(0);
});

it('skips a create chunk once the status is deleted and still settles it', function () {
    Http::fake();
    Queue::fake();

    $profile = fanoutLocalProfile();
    $status = Status::factory()->create(['profile_id' => $profile->id, 'type' => 'photo']);

    ActivityPubFanoutService::dispatch(
        $profile,
        ['type' => 'Create'],
        ['https://remote.example/inbox'],
        false,
        (int) $status->id
    );

    $status->delete();

    fanoutInProduction(fn () => (new DeliverActivityChunk(
        (int) $profile->id,
        ['type' => 'Create'],
        ['https://remote.example/inbox'],
        false,
        (int) $status->id
    ))->handle());

    Http::assertNothingSent();
    expect(ActivityPubFanoutService::pending((int) $status->id))->toBe(0);
});

it('holds the delete until the create fanout has finished', function () {
    Queue::fake();

    $profile = fanoutLocalProfile();

    ActivityPubFanoutService::dispatch(
        $profile,
        ['type' => 'Create'],
        ['https://remote.example/inbox'],
        false,
        789
    );

    $job = (new DeliverStatusDeleteActivity(
        (int) $profile->id,
        789,
        ['type' => 'Delete'],
        ['https://remote.example/inbox']
    ))->withFakeQueueInteractions();

    $job->handle();

    $job->assertReleased(DeliverStatusDeleteActivity::WAIT_SECONDS);
    Queue::assertPushed(DeliverActivityChunk::class, 1);

    ActivityPubFanoutService::settle(789);

    $job = (new DeliverStatusDeleteActivity(
        (int) $profile->id,
        789,
        ['type' => 'Delete'],
        ['https://remote.example/inbox']
    ))->withFakeQueueInteractions();

    $job->handle();

    $job->assertNotReleased();
    Queue::assertPushed(
        DeliverActivityChunk::class,
        fn (DeliverActivityChunk $job) => ($job->activity()['type'] ?? null) === 'Delete' && $job->statusId() === null
    );
});

it('deletes the status locally before queueing the federated delete', function () {
    Queue::fake();
    config(['federation.activitypub.enabled' => true]);

    $profile = fanoutLocalProfile();
    $profile->status_count = 3;
    $profile->saveQuietly();

    $remote = Profile::factory()->remote()->create([
        'inbox_url' => 'https://remote.example/users/bob/inbox',
        'sharedInbox' => 'https://remote.example/inbox',
    ]);

    Follower::create([
        'profile_id' => $remote->id,
        'following_id' => $profile->id,
        'local_profile' => false,
    ]);

    $status = Status::factory()->create(['profile_id' => $profile->id, 'type' => 'photo']);

    (new StatusDelete($status))->handle();

    expect(Status::find($status->id))->toBeNull();
    expect((int) $profile->fresh()->status_count)->toBe(2);

    Queue::assertPushedOn(
        'high',
        DeliverStatusDeleteActivity::class,
        fn (DeliverStatusDeleteActivity $job) => $job->statusId() === (int) $status->id
            && $job->inboxes() === ['https://remote.example/inbox']
            && ($job->activity()['type'] ?? null) === 'Delete'
    );

    Queue::assertNotPushed(DeliverActivityChunk::class);
});
