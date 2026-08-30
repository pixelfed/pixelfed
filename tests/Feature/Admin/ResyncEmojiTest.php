<?php

use App\Models\CustomEmoji;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['federation.custom_emoji.enabled' => true]);
});

/*
|--------------------------------------------------------------------------
| admin:resyncemoji
|--------------------------------------------------------------------------
|
| Re-downloads specific remote custom emoji from their origin URL and stores
| them locally. These tests cover the command's routing/decision branches
| without performing a real network fetch.
|
*/

it('reports not_found for filenames with no matching emoji row', function () {
    $this->artisan('admin:resyncemoji', ['files' => 'doesnotexist.png', '--force' => true])
        ->expectsOutputToContain('not found in DB: doesnotexist.png')
        ->expectsOutputToContain('not_found=1')
        ->assertExitCode(1);
});

it('skips local emoji that have no origin url', function () {
    CustomEmoji::create([
        'shortcode' => ':localonly:',
        'domain' => config('pixelfed.domain.app'),
        'media_path' => 'emoji/500.png',
        'image_remote_url' => null,
    ]);

    $this->artisan('admin:resyncemoji', ['files' => '500.png', '--force' => true])
        ->expectsOutputToContain('skip (no origin url, local emoji): 500.png')
        ->expectsOutputToContain('skipped=1')
        ->assertExitCode(0);
});

it('does not write anything for remote emoji in dry-run mode', function () {
    CustomEmoji::create([
        'shortcode' => ':remote:',
        'domain' => 'mastodon.example',
        'media_path' => 'emoji/600.png',
        'image_remote_url' => 'https://mastodon.example/emoji/600.png',
    ]);

    $this->artisan('admin:resyncemoji', ['files' => '600.png', '--dry-run' => true, '--force' => true])
        ->expectsOutputToContain('[dry-run] would resync: 600.png')
        ->expectsOutputToContain('resynced=1')
        ->assertExitCode(0);
});

it('fails when custom emoji federation is disabled', function () {
    config(['federation.custom_emoji.enabled' => false]);

    $this->artisan('admin:resyncemoji', ['files' => '600.png', '--force' => true])
        ->expectsOutputToContain('not enabled')
        ->assertExitCode(1);
});
