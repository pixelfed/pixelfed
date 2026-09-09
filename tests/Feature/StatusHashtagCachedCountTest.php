<?php

use App\Models\Hashtag;
use App\Models\StatusHashtag;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| StatusHashtag model-delete fires observer
|--------------------------------------------------------------------------
|
| Deleting status_hashtags during status/account deletion must fire the
| eloquent "deleted" event so StatusHashtagObserver decrements
| hashtags.cached_count. A query-builder ->delete() bypasses the event;
| ->get()->each->delete() (the fix) fires it per row.
|
*/

function seedStatusHashtag(): StatusHashtag
{
    $hashtag = Hashtag::create(['name' => 'sunset', 'slug' => 'sunset']);

    return StatusHashtag::create([
        'status_id' => 900000000000000001,
        'hashtag_id' => $hashtag->id,
        'profile_id' => 123,
        'status_visibility' => 'public',
    ]);
}

it('fires the model deleted event for a model-based delete (the fix)', function () {
    seedStatusHashtag();

    Event::fake();

    StatusHashtag::whereStatusId(900000000000000001)->get()->each->delete();

    Event::assertDispatched('eloquent.deleted: '.StatusHashtag::class);
});

it('does not fire the model deleted event for a query-builder delete (the bug)', function () {
    seedStatusHashtag();

    Event::fake();

    StatusHashtag::whereStatusId(900000000000000001)->delete();

    Event::assertNotDispatched('eloquent.deleted: '.StatusHashtag::class);
});
