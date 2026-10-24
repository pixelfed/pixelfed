<?php

use App\Http\Controllers\AdminController;
use App\Models\CustomEmoji;
use App\Services\CustomEmojiService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Custom emoji admin cache invalidation
|--------------------------------------------------------------------------
|
| - delete must forget the per-shortcode key scan() reads (not only the bulk key)
| - toggle-active must forget the bulk pf:custom_emoji key backing the API
|
*/

beforeEach(function () {
    config(['federation.custom_emoji.enabled' => true]);
    config(['instance.enable_cc' => false]);
    Storage::fake('public');
});

it('forgets the per-shortcode scan cache when an emoji is deleted', function () {
    $emoji = CustomEmoji::create([
        'shortcode' => ':boom:',
        'domain' => config('pixelfed.domain.app'),
        'media_path' => 'emoji/boom.png',
        'disabled' => false,
    ]);

    // Warm the per-shortcode cache with a non-null tag.
    expect(CustomEmoji::scan('hello :boom:', false))->toHaveCount(1);

    (new AdminController)->customEmojiDelete(new Request, $emoji->id);

    expect(CustomEmoji::count())->toBe(0);
    // The deleted emoji must stop rendering immediately.
    expect(CustomEmoji::scan('hello :boom:', false))->toBeEmpty();
});

it('forgets the global API cache when an emoji is toggled', function () {
    $emoji = CustomEmoji::create([
        'shortcode' => ':spark:',
        'domain' => config('pixelfed.domain.app'),
        'media_path' => 'emoji/spark.png',
        'disabled' => false,
    ]);

    // Warm the bulk API payload cache (rememberForever) with visible_in_picker=true.
    $before = collect(json_decode(CustomEmojiService::all(), true))->firstWhere('shortcode', 'spark');
    expect($before['visible_in_picker'])->toBeTrue();

    (new AdminController)->customEmojiToggleActive(new Request, $emoji->id);

    $after = collect(json_decode(CustomEmojiService::all(), true))->firstWhere('shortcode', 'spark');
    expect($after['visible_in_picker'])->toBeFalse();
});
