<?php

use App\Http\Controllers\ImportPostController;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Instagram import album classification
|--------------------------------------------------------------------------
|
| determinePostType maps an album's file extensions to statuses.type. A mixed
| photo+video album must be photo:video:album (matching the native compose
| path). The check used multi-arg Collection::contains(), which is a
| where-filter that is always false, so mixed albums were mislabeled
| video:album and rendered as broken video cards.
|
*/

function classifyExts(array $exts): string
{
    $controller = new ImportPostController;
    $method = new ReflectionMethod($controller, 'determinePostType');
    $method->setAccessible(true);

    return $method->invoke($controller, collect($exts));
}

it('classifies a mixed photo+video album as photo:video:album', function () {
    expect(classifyExts(['mp4', 'jpg']))->toBe('photo:video:album');
});

it('classifies a mixed album with png/webp/jpeg as photo:video:album', function () {
    expect(classifyExts(['mp4', 'png']))->toBe('photo:video:album')
        ->and(classifyExts(['mp4', 'webp']))->toBe('photo:video:album')
        ->and(classifyExts(['mp4', 'jpeg']))->toBe('photo:video:album');
});

it('classifies a video-only album as video:album', function () {
    expect(classifyExts(['mp4', 'mp4']))->toBe('video:album');
});

it('classifies a photo-only album as photo:album', function () {
    expect(classifyExts(['jpg', 'png']))->toBe('photo:album');
});

it('classifies single-item albums by their extension', function () {
    expect(classifyExts(['jpg']))->toBe('photo')
        ->and(classifyExts(['mp4']))->toBe('video');
});
