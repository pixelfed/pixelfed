<?php

use App\Models\Media;
use App\Models\User;
use App\Util\Blurhash\Base83;
use App\Util\Blurhash\Color;
use App\Util\Media\Blurhash;
use App\Util\Media\Image;
use App\Util\Media\ImageDriverManager;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Blurhash generation
|--------------------------------------------------------------------------
|
| The hasher used to call GD directly no matter which image driver was
| configured. Under vips that meant dark placeholders for transparent PNGs
| (libvips leaves black behind transparent pixels, the hasher ignored alpha),
| the default hash for every webp, and an uncaught \Error that failed the
| thumbnail job on hosts without ext-gd. It now decodes through the
| configured driver and flattens transparency before sampling.
|
*/

beforeEach(function () {
    try {
        ImageDriverManager::createImageManager();
    } catch (Throwable $e) {
        test()->markTestSkipped('Image driver "'.config('image.driver').'" is not available: '.$e->getMessage());
    }
});

function blurhashFixture(int $width, int $height, ?string $fill = null, string $format = 'png', ?string $driver = null): string
{
    $image = ImageDriverManager::createImageManager([], $driver)->createImage($width, $height);

    if ($fill !== null) {
        $image = $image->fill($fill);
    }

    $encoder = $format === 'webp' ? new WebpEncoder(90) : new PngEncoder;

    return $image->encode($encoder)->toString();
}

function blurhashAverageColor(string $hash): array
{
    $value = Base83::decode(substr($hash, 2, 4));

    return [$value >> 16, ($value >> 8) & 255, $value & 255];
}

it('hashes a solid colour image to that colour', function () {
    $hash = Blurhash::fromBinary(blurhashFixture(64, 48, '3366cc'));

    expect($hash)->toBeString()->toHaveLength(36);

    [$r, $g, $b] = blurhashAverageColor($hash);
    expect($r)->toBeBetween(48, 54);
    expect($g)->toBeBetween(99, 105);
    expect($b)->toBeBetween(201, 207);
});

it('composites transparent areas onto the background instead of hashing them as black', function () {
    $hash = Blurhash::fromBinary(blurhashFixture(64, 48));

    foreach (blurhashAverageColor($hash) as $channel) {
        expect($channel)->toBeGreaterThanOrEqual(250);
    }
});

it('does not overflow a fully saturated channel into its neighbour', function () {
    expect(Color::tosRGB(1.0))->toBe(255);

    $hash = Blurhash::fromBinary(blurhashFixture(32, 32, 'ffffff'));

    expect(blurhashAverageColor($hash))->toBe([255, 255, 255]);
});

it('hashes webp thumbnails instead of returning the default hash', function () {
    try {
        $webp = blurhashFixture(64, 64, 'cc3333', 'webp');
    } catch (Throwable $e) {
        test()->markTestSkipped('Configured image driver cannot encode webp: '.$e->getMessage());
    }

    $file = tempnam(sys_get_temp_dir(), 'blurhash_test_');
    file_put_contents($file, $webp);

    $media = new Media;
    $media->mime = 'image/webp';
    $media->thumbnail_path = 'public/m/photo_thumb.webp';

    try {
        $hash = Blurhash::generate($media, $file);
    } finally {
        @unlink($file);
    }

    expect($hash)->not->toBe(Blurhash::DEFAULT_HASH);
    expect(blurhashAverageColor($hash)[0])->toBeGreaterThan(190);
});

it('returns the default hash for bytes that cannot be decoded', function () {
    expect(Blurhash::fromBinary('definitely not an image'))->toBeNull();

    $file = tempnam(sys_get_temp_dir(), 'blurhash_test_');
    file_put_contents($file, 'definitely not an image');

    $media = new Media;
    $media->mime = 'image/jpeg';
    $media->thumbnail_path = 'public/m/photo_thumb.jpg';

    try {
        expect(Blurhash::generate($media, $file))->toBe(Blurhash::DEFAULT_HASH);
    } finally {
        @unlink($file);
    }
});

it('falls back to gd when the configured driver is unavailable', function () {
    if (! function_exists('imagecreatefromstring')) {
        test()->markTestSkipped('ext-gd is not installed.');
    }

    $png = blurhashFixture(64, 48, '3366cc', 'png', 'gd');

    // Unavailability is a DECODE-time property, not a build-time one:
    // Intervention constructs a manager lazily, so createImageManager() never
    // throws for a missing runtime. fromBinary() only falls back when the
    // actual decode fails, so probe the same way.
    $decodes = function (string $driver) use ($png): bool {
        try {
            ImageDriverManager::createImageManager(['decodeAnimation' => false], $driver)
                ->decodeBinary($png);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    };

    $unavailable = collect(['vips', 'imagick'])->first(fn ($driver) => ! $decodes($driver));

    if ($unavailable !== null) {
        // A driver is genuinely missing: configure it so fromBinary() hits a
        // decode failure on the leading candidate and must fall back to gd,
        // still producing a valid hash.
        Config::set('image.driver', $unavailable);

        expect(Blurhash::fromBinary($png))->toBeString()->toHaveLength(36);

        return;
    }

    // Every driver decodes on this host, so a missing-driver scenario cannot
    // be reproduced without mocking Intervention internals. Assert the
    // fallback invariant that makes the degradation possible instead: the
    // candidate list always ends with gd (the universal fallback), and gd
    // decodes the bytes to a valid hash.
    expect(ImageDriverManager::candidateDrivers('vips'))->toContain('gd');

    Config::set('image.driver', 'gd');
    expect(Blurhash::fromBinary($png))->toBeString()->toHaveLength(36);
});

it('stores a blurhash when a thumbnail is generated', function () {
    Config::set('filesystems.default', 's3');
    Config::set('pixelfed.optimize_image', false);
    Storage::fake('s3', ['url' => 'https://cdn.test']);

    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;

    $mediaPath = 'public/m/_v2/'.$pid.'/ee/ff/photo.png';
    $pngBytes = blurhashFixture(800, 600, '3366cc');
    Storage::disk('s3')->put($mediaPath, $pngBytes);

    $media = Media::create([
        'profile_id' => $pid,
        'user_id' => $user->id,
        'media_path' => $mediaPath,
        'mime' => 'image/png',
        'size' => strlen($pngBytes),
        'remote_media' => false,
        'order' => 0,
    ]);

    (new Image)->resizeThumbnail($media);
    $media->refresh();

    expect($media->thumbnail_path)->toBe('public/m/_v2/'.$pid.'/ee/ff/photo_thumb.png');
    expect($media->blurhash)->toBeString()->not->toBe(Blurhash::DEFAULT_HASH);

    [$r, $g, $b] = blurhashAverageColor($media->blurhash);
    expect($r)->toBeBetween(48, 54);
    expect($g)->toBeBetween(99, 105);
    expect($b)->toBeBetween(201, 207);
});
