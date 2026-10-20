<?php

use App\Models\ImportPost;
use App\Models\Status;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| ImportService::getImportedFiles() returns already-imported media uris
|--------------------------------------------------------------------------
|
| The import wizard excludes any post whose media uri is already present in
| the "existing" list (!this.existing.includes(...)). So getImportedFiles()
| must return the uris of rows that already have a resolvable Status, and must
| not return pending rows (status_id null). A seeded public Status makes
| StatusService::get() resolve non-null for the imported row.
|
*/

function makeImportPostForExistingTest(User $user, ?int $statusId, string $uri): ImportPost
{
    $ip = new ImportPost;
    $ip->profile_id = $user->profile_id;
    $ip->user_id = $user->id;
    $ip->service = 'instagram';
    $ip->filename = $uri;
    $ip->media_count = 1;
    $ip->status_id = $statusId;
    $ip->media = [['uri' => $uri]];
    $ip->save();

    return $ip;
}

it('returns already-imported media uris and excludes pending rows', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile_id;

    $status = Status::factory()->create(['profile_id' => $pid]);
    makeImportPostForExistingTest($user, $status->id, 'a.jpg');
    makeImportPostForExistingTest($user, null, 'b.jpg');

    $imported = ImportService::getImportedFiles($pid, true)->all();

    expect($imported)->toContain('a.jpg')
        ->and($imported)->not->toContain('b.jpg');
});

it('returns no imported uris when a profile has only pending rows', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile_id;

    makeImportPostForExistingTest($user, null, 'b.jpg');

    $imported = ImportService::getImportedFiles($pid, true)->all();

    expect($imported)->toBeEmpty();
});
