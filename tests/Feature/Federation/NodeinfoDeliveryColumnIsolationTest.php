<?php

use App\Jobs\InstancePipeline\FetchNodeinfoPipeline;
use App\Models\Instance;
use App\Services\DeliveryHostService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Nodeinfo cooldown must not touch the ActivityPub delivery-backoff columns
|--------------------------------------------------------------------------
|
| FetchNodeinfoPipeline reused delivery_timeout / delivery_next_after, so a
| Nodeinfo failure marked a sub-threshold host unavailable for AP delivery
| for 14h. Nodeinfo now owns nodeinfo_timeout / nodeinfo_next_after; the
| delivery columns are written only by DeliveryHostService.
|
*/

beforeEach(function () {
    config(['federation.activitypub.delivery.failure_threshold' => 5]);
    Http::fake(['*' => Http::response('', 404)]); // force the Nodeinfo failure branch
    DeliveryHostService::reset('sub.example');
});

it('writes nodeinfo cooldown columns and leaves delivery columns untouched on failure', function () {
    $instance = new Instance;
    $instance->domain = 'sub.example';
    $instance->delivery_failures = 1;
    $instance->delivery_timeout = false;
    $instance->delivery_next_after = null;
    $instance->save();

    (new FetchNodeinfoPipeline($instance))->handle();

    $instance->refresh();

    // Nodeinfo cooldown armed on its own columns.
    expect((bool) $instance->nodeinfo_timeout)->toBeTrue();
    expect($instance->nodeinfo_next_after)->not->toBeNull();

    // Delivery-backoff columns untouched by the Nodeinfo crawl.
    expect((bool) $instance->delivery_timeout)->toBeFalse();
    expect($instance->delivery_next_after)->toBeNull();
});

it('does not report a sub-threshold host unavailable after a nodeinfo failure', function () {
    $instance = new Instance;
    $instance->domain = 'sub.example';
    $instance->delivery_failures = 1; // well under threshold of 5
    $instance->delivery_timeout = false;
    $instance->delivery_next_after = null;
    $instance->save();

    (new FetchNodeinfoPipeline($instance))->handle();

    // The AP delivery layer must still consider the host available.
    expect(DeliveryHostService::isUnavailable('sub.example'))->toBeFalse();
});
