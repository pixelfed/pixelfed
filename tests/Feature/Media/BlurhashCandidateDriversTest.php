<?php

use App\Util\Media\Blurhash;
use App\Util\Media\ImageDriverManager;

/*
|--------------------------------------------------------------------------
| Blurhash driver fallback parity
|--------------------------------------------------------------------------
|
| Blurhash generation must try the same drivers as the thumbnail pipeline.
| Its old local list only added gd, so on a vips-configured host with imagick
| but no gd, thumbnails degraded to imagick while blurhash fell back to the
| placeholder hash. Blurhash now delegates to ImageDriverManager so the two
| stay in lockstep.
|
*/

function blurhashCandidateDrivers(): array
{
    $method = new ReflectionMethod(Blurhash::class, 'candidateDrivers');
    $method->setAccessible(true);

    return $method->invoke(null);
}

it('matches the thumbnail pipeline driver list exactly for every configured driver', function (string $driver) {
    config(['image.driver' => $driver]);

    // Delegation guarantee: identical to ImageDriverManager regardless of which
    // image extensions this build has. Under the old duplicated list these
    // could diverge (imagick omitted, gd gated on function_exists).
    expect(blurhashCandidateDrivers())->toBe(ImageDriverManager::candidateDrivers());
})->with(['vips', 'imagick', 'gd']);

it('includes imagick as a fallback when the extension is loaded', function () {
    if (! extension_loaded('imagick')) {
        $this->markTestSkipped('ext-imagick not available in this environment');
    }

    expect(blurhashCandidateDrivers())->toContain('imagick');
});

it('leads with the configured driver', function () {
    config(['image.driver' => 'vips']);

    expect(blurhashCandidateDrivers()[0])->toBe('vips');
});
