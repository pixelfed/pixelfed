<?php

use App\Models\CustomFilter;
use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
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

    $statuses = collect(range(1, 3))->map(function () use ($author) {
        $s = Status::factory()->create([
            'profile_id' => $author->profile_id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'caption' => 'hideme sunset photo',
            'rendered' => 'hideme sunset photo',
            'in_reply_to_id' => null,
            'reblog_of_id' => null,
        ]);
        StatusService::getMastodon($s->id, false);

        return $s;
    });

    foreach ($statuses as $s) {
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

    $link = $response->headers->get('Link');
    expect($link)->not->toBeNull()
        ->and($link)->toContain('rel="next"')
        ->and($link)->toContain('max_id='.$statuses->pluck('id')->map(fn ($id) => (int) $id)->min());
});
