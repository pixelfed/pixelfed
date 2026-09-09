<?php

use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    // The dedupe command runs BEFORE the unique(uri) constraint is added, so
    // drop it here to allow seeding the duplicate rows it is meant to clean up.
    Schema::table('statuses', function ($table) {
        $table->dropUnique('statuses_uri_unique');
    });
});

/*
|--------------------------------------------------------------------------
| status:dedup deterministic keep
|--------------------------------------------------------------------------
|
| The dedupe command must deterministically keep the earliest (MIN id) status
| per uri and delete the rest. A non-aggregated id under GROUP BY is
| nondeterministic and could keep an arbitrary duplicate.
|
*/

it('keeps the lowest-id status per uri and deletes the duplicates', function () {
    Bus::fake();

    $author = User::factory()->create();
    $author->refresh();

    $uri = 'https://remote.example/users/bob/statuses/42';

    // Snowflake ids are time-ordered, so the first created has the min id.
    $first = Status::factory()->create(['profile_id' => $author->profile_id, 'type' => 'photo', 'uri' => $uri]);
    $second = Status::factory()->create(['profile_id' => $author->profile_id, 'type' => 'photo', 'uri' => $uri]);
    $third = Status::factory()->create(['profile_id' => $author->profile_id, 'type' => 'photo', 'uri' => $uri]);

    $ids = collect([$first->id, $second->id, $third->id]);
    $keepId = $ids->min();
    $deleteIds = $ids->reject(fn ($id) => $id === $keepId)->values();

    $this->artisan('status:dedup')->assertExitCode(0);

    $statusIdOf = function ($job) {
        $ref = new ReflectionProperty($job, 'status');
        $ref->setAccessible(true);

        return $ref->getValue($job)->id;
    };

    // The two higher-id duplicates are dispatched for deletion.
    Bus::assertDispatched(StatusDelete::class, function ($job) use ($deleteIds, $statusIdOf) {
        return $deleteIds->contains($statusIdOf($job));
    });

    // The kept (min id) status is never dispatched for deletion.
    Bus::assertNotDispatched(StatusDelete::class, function ($job) use ($keepId, $statusIdOf) {
        return $statusIdOf($job) === $keepId;
    });

    // Exactly two deletions (the two duplicates).
    Bus::assertDispatchedTimes(StatusDelete::class, 2);
});
