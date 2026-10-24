<?php

use App\Jobs\StatusPipeline\StatusTagsPipeline;
use App\Models\Hashtag;
use App\Models\Status;
use App\Models\StatusHashtag;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| StatusTagsPipeline hashtag find-or-create is idempotent
|--------------------------------------------------------------------------
|
| Two federated statuses that share a brand-new hashtag must resolve to a
| single Hashtag row. The Postgres branch previously used lockForUpdate +
| create, which raced to SQLSTATE 23505; firstOrCreate (createOrFirst) is the
| race-safe idiom used here now.
|
*/

function hashtagActivity(string $name): array
{
    return [
        'tag' => [
            [
                'type' => 'Hashtag',
                'href' => 'https://remote.example/tags/'.$name,
                'name' => '#'.$name,
            ],
        ],
    ];
}

it('resolves a shared brand-new hashtag to one row across two statuses', function () {
    $user = User::factory()->create();
    $user->refresh();

    $statusA = Status::factory()->create(['profile_id' => $user->profile_id, 'type' => 'photo']);
    $statusB = Status::factory()->create(['profile_id' => $user->profile_id, 'type' => 'photo']);

    (new StatusTagsPipeline(hashtagActivity('brandnewtag'), $statusA))->handle();
    (new StatusTagsPipeline(hashtagActivity('brandnewtag'), $statusB))->handle();

    // Exactly one hashtag row for the shared slug.
    expect(Hashtag::where('slug', 'brandnewtag')->count())->toBe(1);

    $hashtag = Hashtag::where('slug', 'brandnewtag')->first();

    // Both statuses link to the same hashtag.
    expect(StatusHashtag::where('hashtag_id', $hashtag->id)->where('status_id', $statusA->id)->exists())->toBeTrue();
    expect(StatusHashtag::where('hashtag_id', $hashtag->id)->where('status_id', $statusB->id)->exists())->toBeTrue();
});

it('reuses an existing hashtag row rather than erroring', function () {
    $user = User::factory()->create();
    $user->refresh();

    $existing = Hashtag::create(['name' => 'existingtag', 'slug' => 'existingtag']);

    $status = Status::factory()->create(['profile_id' => $user->profile_id, 'type' => 'photo']);
    (new StatusTagsPipeline(hashtagActivity('existingtag'), $status))->handle();

    expect(Hashtag::where('slug', 'existingtag')->count())->toBe(1);
    expect(StatusHashtag::where('hashtag_id', $existing->id)->where('status_id', $status->id)->exists())->toBeTrue();
});

it('keeps distinct names that share a base slug as separate rows', function () {
    $user = User::factory()->create();
    $user->refresh();

    // 'c' and 'c++' both slugify to base 'c'; they must remain distinct rows.
    $statusA = Status::factory()->create(['profile_id' => $user->profile_id, 'type' => 'photo']);
    $statusB = Status::factory()->create(['profile_id' => $user->profile_id, 'type' => 'photo']);

    (new StatusTagsPipeline(hashtagActivity('c'), $statusA))->handle();
    (new StatusTagsPipeline(hashtagActivity('c++'), $statusB))->handle();

    expect(Hashtag::where('name', 'c')->count())->toBe(1);
    expect(Hashtag::where('name', 'c++')->count())->toBe(1);

    // Slugs must differ so the unique slug index is not violated.
    $slugC = Hashtag::where('name', 'c')->value('slug');
    $slugCpp = Hashtag::where('name', 'c++')->value('slug');
    expect($slugC)->not->toBe($slugCpp);
});
