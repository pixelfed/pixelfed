<?php

use App\Models\Status;
use App\Models\User;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
    Cache::forget('api:v1:timelines:public:cache_check');
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
    Cache::forget('api:v1:timelines:public:cache_check');
});

it('does not rebuild a warm local timeline on each request', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    // Eligible local statuses exist in the DB, but the cache is pre-seeded with
    // only a subset. A re-warm would delete and rebuild from the DB, pulling in
    // the other statuses; a correct guard leaves the seeded set untouched.
    $all = collect(range(1, 4))->map(function () {
        $s = Status::factory()->create([
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'local' => true,
            'in_reply_to_id' => null,
            'reblog_of_id' => null,
            'uri' => null,
        ]);
        StatusService::get($s->id);

        return $s;
    });

    $seeded = $all->take(2);
    foreach ($seeded as $s) {
        PublicTimelineService::add($s->id);
    }

    Passport::actingAs($viewer, ['read']);

    $this->getJson('/api/v1/timelines/public?limit=10')->assertOk();

    $cached = collect(PublicTimelineService::get(0, -1))->map(fn ($v) => (int) $v)->sort()->values()->all();
    $expected = $seeded->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

    expect($cached)->toBe($expected);
});
