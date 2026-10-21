<?php

use App\Models\CustomFilter;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\SnowflakeService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

afterEach(function () {
    Redis::flushdb();
});

it('still returns a next link when the whole page is filtered out', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    $statuses = collect([30, 20, 10])->map(function ($minutesAgo) use ($author) {
        $s = Status::factory()->create([
            'profile_id' => $author->profile_id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'caption' => 'hideme sunset photo',
            'rendered' => 'hideme sunset photo',
            'in_reply_to_id' => null,
            'reblog_of_id' => null,
            'created_at' => now()->subMinutes($minutesAgo),
        ]);
        StatusService::getMastodon($s->id, false);

        return $s;
    });

    // A remote post holds the lowest id in the window and the most recent
    // date, so the numeric minimum and the display-order oldest differ.
    $remoteAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $lowIdRecent = Status::factory()->create([
        'id' => SnowflakeService::byDate(now()->subMonths(8)),
        'profile_id' => $remoteAuthor->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'uri' => 'https://remote.example/p/'.uniqid(),
        'caption' => 'hideme sunset photo',
        'rendered' => 'hideme sunset photo',
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'created_at' => now()->subMinutes(1),
    ]);
    StatusService::getMastodon($lowIdRecent->id, false);

    foreach ($statuses->concat([$lowIdRecent]) as $s) {
        HomeTimelineService::add($pid, $s->id);
    }

    $filter = CustomFilter::create([
        'profile_id' => $pid,
        'title' => 'hide sunsets',
        'context' => ['home'],
        'action' => 1,
    ]);
    $filter->keywords()->create(['keyword' => 'hideme', 'whole_word' => false]);
    Cache::forget('filters:v3:'.$pid);

    Passport::actingAs($viewer, ['read']);

    $response = $this->getJson('/api/v1/timelines/home?limit=5')->assertOk();

    expect($response->json())->toBe([]);

    // The continuation cursor has to be the oldest member in display order,
    // which is not the lowest id once a remote post is in the window.
    $displayOldest = (int) $statuses->sortBy(fn ($s) => $s->created_at->timestamp)->first()->id;

    expect((int) $lowIdRecent->id)->toBeLessThan($displayOldest);

    $link = $response->headers->get('Link');
    expect($link)->not->toBeNull()
        ->and($link)->toContain('rel="next"')
        ->and($link)->toContain('max_id='.$displayOldest);
});
