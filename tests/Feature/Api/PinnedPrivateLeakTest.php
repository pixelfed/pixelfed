<?php

use App\Models\Media;
use App\Models\Status;
use App\Models\User;
use App\Services\MediaService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Pinned posts must respect per-viewer visibility
|--------------------------------------------------------------------------
|
| The pinned-only branch of accountStatuses returned pinned rows without the
| per-viewer scope filter the regular paginator applies. After an admin
| demotes a pinned post to private (pinned_order is preserved), anonymous and
| non-follower viewers could read the private post from the pinned slot, media
| and all. These match Profile.vue's request params { limit, pinned, only_media }.
|
*/

beforeEach(function () {
    Redis::spy();
});

function pinnedStatusWithMedia(int $profileId, string $scope): Status
{
    $status = Status::factory()->create([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => $scope,
        'visibility' => $scope,
        'pinned_order' => 1,
    ]);

    // A media row so the only_media filter keeps the post: the exploit reads
    // an image-bearing pinned post.
    $media = new Media;
    $media->status_id = $status->id;
    $media->profile_id = $profileId;
    $media->media_path = 'public/test.jpg';
    $media->mime = 'image/jpeg';
    $media->order = 1;
    $media->save();

    MediaService::del($status->id);
    StatusService::del($status->id, true);

    return $status;
}

function pinnedEndpoint(int $accountId): string
{
    return "/api/pixelfed/v1/accounts/{$accountId}/statuses?pinned=true&only_media=true&limit=9";
}

function pinnedIds(array $res): array
{
    return collect($res)->pluck('id')->map(fn ($v) => (string) $v)->all();
}

it('does not leak a private pinned post to an anonymous viewer', function () {
    $author = User::factory()->create();
    $author->refresh();

    $status = pinnedStatusWithMedia($author->profile_id, 'private');

    $res = $this->getJson(pinnedEndpoint($author->profile_id))->assertOk();

    expect(pinnedIds($res->json()))->not->toContain((string) $status->id);
    expect($res->getContent())->not->toContain('"visibility":"private"');
});

it('does not leak a private pinned post to a non-follower', function () {
    $author = User::factory()->create();
    $author->refresh();
    $viewer = User::factory()->create();
    $viewer->refresh();

    $status = pinnedStatusWithMedia($author->profile_id, 'private');

    $this->actingAs($viewer);

    $res = $this->getJson(pinnedEndpoint($author->profile_id))->assertOk();

    expect(pinnedIds($res->json()))->not->toContain((string) $status->id);
});

it('still shows a private pinned post to its author', function () {
    $author = User::factory()->create();
    $author->refresh();

    $status = pinnedStatusWithMedia($author->profile_id, 'private');

    $this->actingAs($author);

    $res = $this->getJson(pinnedEndpoint($author->profile_id))->assertOk();

    expect(pinnedIds($res->json()))->toContain((string) $status->id);
});

it('still shows a public pinned post to an anonymous viewer', function () {
    $author = User::factory()->create();
    $author->refresh();

    $status = pinnedStatusWithMedia($author->profile_id, 'public');

    $res = $this->getJson(pinnedEndpoint($author->profile_id))->assertOk();

    expect(pinnedIds($res->json()))->toContain((string) $status->id);
});
