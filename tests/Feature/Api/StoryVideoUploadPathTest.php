<?php

use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    config(['instance.stories.enabled' => true]);
    config(['filesystems.default' => 'local']);
});

it('opens the uploaded story video via the local disk with a disk-relative path', function () {
    $user = User::factory()->create();
    $user->refresh();
    $this->actingAs($user);

    $captured = [];

    // Swap the FFMpeg facade for a spy so we never shell out to ffprobe.
    // fromDisk()/open() return the spy itself (fluent chain), and we record
    // the disk + path the controller asks for.
    $spy = Mockery::mock();
    $spy->shouldReceive('fromDisk')->andReturnUsing(function ($disk) use (&$captured, $spy) {
        $captured['disk'] = $disk;

        return $spy;
    });
    $spy->shouldReceive('open')->andReturnUsing(function ($path) use (&$captured, $spy) {
        $captured['path'] = $path;

        return $spy;
    });
    $spy->shouldReceive('getDurationInSeconds')->andReturn(10);
    FFMpeg::swap($spy);

    $response = $this->postJson('/api/web/stories/v1/add', [
        'file' => UploadedFile::fake()->create('story.mp4', 500, 'video/mp4'),
    ]);

    $response->assertOk();
    expect($response->json('media_type'))->toBe('video');
    expect($response->json('media_duration'))->toBe(10);

    // Opened from the local disk...
    expect($captured['disk'])->toBe('local');

    // ...with a disk-relative path (the regression passed an absolute path).
    $path = $captured['path'];
    expect($path)->not->toStartWith('/');
    expect($path)->not->toContain(storage_path());
    expect($path)->toStartWith('public/_esm.t3/');

    // And that disk-relative path resolves to the file that was actually
    // stored, so ffprobe would find it.
    Storage::disk('local')->assertExists($path);

    $story = Story::whereProfileId($user->profile_id)->firstOrFail();
    expect($story->type)->toBe('video');
    expect($story->path)->toBe($path);
});

it('rejects a story video longer than the allowed duration', function () {
    $user = User::factory()->create();
    $user->refresh();
    $this->actingAs($user);

    $spy = Mockery::mock();
    $spy->shouldReceive('fromDisk')->andReturnSelf();
    $spy->shouldReceive('open')->andReturnSelf();
    $spy->shouldReceive('getDurationInSeconds')->andReturn(501);
    FFMpeg::swap($spy);

    $response = $this->postJson('/api/web/stories/v1/add', [
        'file' => UploadedFile::fake()->create('story.mp4', 500, 'video/mp4'),
    ]);

    $response->assertStatus(422);
    expect(Story::whereProfileId($user->profile_id)->count())->toBe(0);
});
