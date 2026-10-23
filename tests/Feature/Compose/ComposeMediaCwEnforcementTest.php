<?php

use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Models\Media;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Compose media CW enforcement
|--------------------------------------------------------------------------
|
| When a user profile has `cw` enabled, all composed media posts must be
| marked sensitive (is_nsfw = true) on both the Status and every attached
| Media row, even if the compose payload did not include `cw: true`.
| Furthermore, any provided spoiler_text must be retained.
|
*/

beforeEach(function () {
    Redis::spy();
    Queue::fake([NewStatusPipeline::class]);
    config(['instance.enable_cc' => false]);
    config(['costar.enabled' => false]);
    config(['federation.activitypub.enabled' => false]);
    Storage::fake('local');
});

function createDraftMedia(User $user): Media
{
    $mediaPath = 'public/m/_v2/'.$user->profile_id.'/cw/test.jpg';
    Storage::disk('local')->put($mediaPath, 'placeholder');

    $media = new Media;
    $media->user_id = $user->id;
    $media->profile_id = $user->profile_id;
    $media->media_path = $mediaPath;
    $media->mime = 'image/jpeg';
    $media->size = 100;
    $media->is_nsfw = false;
    $media->save();

    return $media;
}

it('enforces profile CW on media attachments and status when cw is omitted or false', function () {
    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;
    $profile->cw = true;
    $profile->save();

    $media = createDraftMedia($user);

    $response = $this->actingAs($user)
        ->postJson('/api/compose/v0/publish', [
            'media' => [
                ['id' => $media->id],
            ],
            'caption' => 'Post with profile CW enabled',
            'visibility' => 'public',
            'cw' => false,
        ]);

    $response->assertOk();

    $status = Status::whereProfileId($profile->id)->latest('id')->first();
    expect($status)->not->toBeNull();
    expect((bool) $status->is_nsfw)->toBeTrue();
    expect((bool) $media->fresh()->is_nsfw)->toBeTrue();
});

it('preserves spoiler_text when profile CW is active and cw is omitted from payload', function () {
    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;
    $profile->cw = true;
    $profile->save();

    $media = createDraftMedia($user);

    $response = $this->actingAs($user)
        ->postJson('/api/compose/v0/publish', [
            'media' => [
                ['id' => $media->id],
            ],
            'caption' => 'Post with spoiler text',
            'spoiler_text' => 'Sensitive topic summary',
            'visibility' => 'public',
        ]);

    $response->assertOk();

    $status = Status::whereProfileId($profile->id)->latest('id')->first();
    expect($status)->not->toBeNull();
    expect((bool) $status->is_nsfw)->toBeTrue();
    expect($status->cw_summary)->toBe('Sensitive topic summary');
    expect((bool) $media->fresh()->is_nsfw)->toBeTrue();
});

it('leaves media and status non-sensitive when neither profile cw nor request cw is set', function () {
    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;
    $profile->cw = false;
    $profile->save();

    $media = createDraftMedia($user);

    $response = $this->actingAs($user)
        ->postJson('/api/compose/v0/publish', [
            'media' => [
                ['id' => $media->id],
            ],
            'caption' => 'Normal post',
            'visibility' => 'public',
            'cw' => false,
        ]);

    $response->assertOk();

    $status = Status::whereProfileId($profile->id)->latest('id')->first();
    expect($status)->not->toBeNull();
    expect((bool) $status->is_nsfw)->toBeFalse();
    expect((bool) $media->fresh()->is_nsfw)->toBeFalse();
});

it('marks media and status sensitive when profile cw is false but request cw is true', function () {
    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;
    $profile->cw = false;
    $profile->save();

    $media = createDraftMedia($user);

    $response = $this->actingAs($user)
        ->postJson('/api/compose/v0/publish', [
            'media' => [
                ['id' => $media->id],
            ],
            'caption' => 'Manually marked CW post',
            'spoiler_text' => 'Explicit CW toggle',
            'visibility' => 'public',
            'cw' => true,
        ]);

    $response->assertOk();

    $status = Status::whereProfileId($profile->id)->latest('id')->first();
    expect($status)->not->toBeNull();
    expect((bool) $status->is_nsfw)->toBeTrue();
    expect($status->cw_summary)->toBe('Explicit CW toggle');
    expect((bool) $media->fresh()->is_nsfw)->toBeTrue();
});
