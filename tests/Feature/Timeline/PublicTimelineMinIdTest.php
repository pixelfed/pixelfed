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

function minIdLocalStatus(): Status
{
    static $seq = 0;

    $status = Status::factory()->create([
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
        'created_at' => now()->subMinutes(1000 - ($seq++)),
    ]);

    StatusService::get($status->id);

    return $status;
}

it('honors min_id when max_id is absent', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $statuses = collect(range(1, 5))
        ->map(fn () => minIdLocalStatus())
        ->sortBy('id')
        ->values();

    foreach ($statuses as $status) {
        PublicTimelineService::add($status->id);
    }

    Cache::put('api:v1:timelines:public:cache_check', 1, 3600);

    $cursor = (int) $statuses[2]->id;

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/public?min_id='.$cursor.'&limit=10')
        ->assertOk()
        ->json();

    $ids = collect($res)->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->not->toBeEmpty();
    foreach ($ids as $id) {
        expect($id)->toBeGreaterThan($cursor);
    }
});
