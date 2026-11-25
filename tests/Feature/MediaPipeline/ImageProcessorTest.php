<?php

use App\Services\Media\ImageProcessor;
use App\Services\Media\ProcessedImage;
use App\Util\Media\ImageDriverManager;

function imageProcessorFor(string $driver, bool $resize = true, ?Closure $optimizer = null): ImageProcessor
{
    try {
        $manager = ImageDriverManager::createImageManager([], $driver);
        $manager->createImage(1, 1);
    } catch (Throwable $e) {
        test()->markTestSkipped('Image driver "'.$driver.'" is not available: '.$e->getMessage());
    }

    return new ImageProcessor($manager, 80, $resize, $optimizer);
}

function imageProcessorJpeg(int $width, int $height): string
{
    $im = imagecreatetruecolor($width, $height);
    imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, imagecolorallocate($im, 51, 102, 204));
    imagefilledrectangle($im, 0, 0, intdiv($width, 4), intdiv($height, 4), imagecolorallocate($im, 220, 30, 30));
    ob_start();
    imagejpeg($im, null, 90);

    return ob_get_clean();
}

function imageProcessorPng(int $width, int $height): string
{
    $im = imagecreatetruecolor($width, $height);
    imagesavealpha($im, true);
    imagealphablending($im, false);
    imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, imagecolorallocatealpha($im, 0, 0, 0, 127));
    imagefilledellipse($im, intdiv($width, 2), intdiv($height, 2), intdiv($width, 2), intdiv($height, 2), imagecolorallocatealpha($im, 30, 200, 90, 0));
    ob_start();
    imagepng($im);

    return ob_get_clean();
}

function imageProcessorExifJpeg(string $jpeg): string
{
    $entry = fn (int $tag, int $type, int $count, string $value) => pack('vvV', $tag, $type, $count).str_pad($value, 4, "\0");
    $rational = fn (array $values) => implode('', array_map(fn (int $n) => pack('VV', $n, 1), $values));
    $gpsOffset = 8 + 2 + 2 * 12 + 4;
    $dataOffset = $gpsOffset + 2 + 4 * 12 + 4;

    $tiff = 'II'.pack('vV', 42, 8)
        .pack('v', 2)
        .$entry(0x0112, 3, 1, pack('v', 6))
        .$entry(0x8825, 4, 1, pack('V', $gpsOffset))
        .pack('V', 0)
        .pack('v', 4)
        .$entry(0x0001, 2, 2, "N\0")
        .$entry(0x0002, 5, 3, pack('V', $dataOffset))
        .$entry(0x0003, 2, 2, "W\0")
        .$entry(0x0004, 5, 3, pack('V', $dataOffset + 24))
        .pack('V', 0)
        .$rational([55, 10, 12])
        .$rational([118, 47, 40]);

    $payload = "Exif\0\0".$tiff;

    return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($payload) + 2).$payload.substr($jpeg, 2);
}

function imageProcessorRun(ImageProcessor $processor, string $bytes, string $extension, string $mode = ImageProcessor::MODE_FULL): ProcessedImage
{
    $path = tempnam(sys_get_temp_dir(), 'pf_proc_test_');
    file_put_contents($path, $bytes);

    try {
        return $processor->process($path, $extension, $mode);
    } finally {
        @unlink($path);
    }
}

function imageProcessorExif(string $bytes): array
{
    $path = tempnam(sys_get_temp_dir(), 'pf_proc_exif_');
    file_put_contents($path, $bytes);

    try {
        return @exif_read_data($path) ?: [];
    } finally {
        @unlink($path);
    }
}

function imageProcessorPixel(string $bytes, int $x, int $y): array
{
    $im = imagecreatefromstring($bytes);

    return imagecolorsforindex($im, imagecolorat($im, $x, $y));
}

dataset('image drivers', ['gd', 'vips']);

it('scales the main image into its orientation box and crops a square thumbnail', function (string $driver) {
    $result = imageProcessorRun(imageProcessorFor($driver), imageProcessorJpeg(3000, 2000), 'jpg');

    expect($result->extension)->toBe('jpg')
        ->and($result->orientation)->toBe('landscape')
        ->and([$result->width, $result->height])->toBe([1620, 1080])
        ->and([$result->originalWidth, $result->originalHeight])->toBe([3000, 2000])
        ->and($result->resized())->toBeTrue()
        ->and(array_slice(getimagesizefromstring($result->main), 0, 2))->toBe([1620, 1080])
        ->and(array_slice(getimagesizefromstring($result->thumbnail), 0, 2))->toBe([640, 640])
        ->and($result->blurhash)->toBeString()->toHaveLength(36);
})->with('image drivers');

it('applies the exif orientation and strips location data from every output', function (string $driver) {
    if (! function_exists('exif_read_data')) {
        test()->markTestSkipped('ext-exif is not installed.');
    }

    $source = imageProcessorExifJpeg(imageProcessorJpeg(4000, 3000));

    expect(imageProcessorExif($source))->toHaveKey('GPSLatitude');

    $result = imageProcessorRun(imageProcessorFor($driver), $source, 'jpg');

    expect($result->orientation)->toBe('portrait')
        ->and(array_slice(getimagesizefromstring($result->main), 0, 2))->toBe([1013, 1350]);

    foreach ([$result->main, $result->thumbnail] as $bytes) {
        $exif = imageProcessorExif($bytes);
        $pixel = imageProcessorPixel($bytes, imagesx(imagecreatefromstring($bytes)) - 3, 3);

        expect($exif)->not->toHaveKey('GPSLatitude')
            ->and($exif)->not->toHaveKey('GPSLongitude')
            ->and($pixel['red'])->toBeGreaterThan(180)
            ->and($pixel['blue'])->toBeLessThan(80);
    }
})->with('image drivers');

it('keeps png uploads as png with their transparency', function (string $driver) {
    $result = imageProcessorRun(imageProcessorFor($driver), imageProcessorPng(2400, 1600), 'png');

    expect($result->extension)->toBe('png')
        ->and($result->mime())->toBe('image/png')
        ->and(imageProcessorPixel($result->main, 1, 1)['alpha'])->toBe(127)
        ->and(imageProcessorPixel($result->thumbnail, 1, 1)['alpha'])->toBe(127);
})->with('image drivers');

it('converts other formats to jpg', function () {
    expect(ImageProcessor::outputExtension('heic'))->toBe('jpg')
        ->and(ImageProcessor::outputExtension('AVIF'))->toBe('jpg')
        ->and(ImageProcessor::outputExtension('jpeg'))->toBe('jpg')
        ->and(ImageProcessor::outputExtension('webp'))->toBe('webp')
        ->and(ImageProcessor::outputExtension('PNG'))->toBe('png');
});

it('classifies square images as square', function () {
    expect(ImageProcessor::orientation(2000, 2000))->toBe('square')
        ->and(ImageProcessor::orientation(2000, 1000))->toBe('landscape')
        ->and(ImageProcessor::orientation(1000, 2000))->toBe('portrait');
});

it('leaves the main image alone when resizing is disabled', function (string $driver) {
    $result = imageProcessorRun(imageProcessorFor($driver, resize: false), imageProcessorJpeg(3000, 2000), 'jpg');

    expect($result->main)->toBeNull()
        ->and([$result->width, $result->height])->toBe([3000, 2000])
        ->and($result->resized())->toBeFalse()
        ->and(array_slice(getimagesizefromstring($result->thumbnail), 0, 2))->toBe([640, 640]);
})->with('image drivers');

it('only builds a thumbnail in thumbnail mode', function (string $driver) {
    $result = imageProcessorRun(imageProcessorFor($driver), imageProcessorJpeg(1620, 1080), 'jpg', ImageProcessor::MODE_THUMBNAIL);

    expect($result->main)->toBeNull()
        ->and(array_slice(getimagesizefromstring($result->thumbnail), 0, 2))->toBe([640, 640]);
})->with('image drivers');

it('runs the file optimizer for gd but not for vips', function (string $driver, int $expected) {
    $calls = 0;
    $processor = imageProcessorFor($driver, optimizer: function (string $path) use (&$calls) {
        $calls++;
    });

    imageProcessorRun($processor, imageProcessorJpeg(3000, 2000), 'jpg');

    expect($calls)->toBe($expected);
})->with([
    'gd' => ['gd', 2],
    'vips' => ['vips', 0],
]);

it('skips the file optimizer when resizing is disabled', function () {
    $calls = 0;
    $processor = imageProcessorFor('gd', resize: false, optimizer: function (string $path) use (&$calls) {
        $calls++;
    });

    imageProcessorRun($processor, imageProcessorJpeg(3000, 2000), 'jpg');

    expect($calls)->toBe(0);
});

it('keeps the encoded bytes when the optimizer throws', function () {
    $processor = imageProcessorFor('gd', optimizer: function (string $path) {
        throw new RuntimeException('optimizer binary missing');
    });

    $result = imageProcessorRun($processor, imageProcessorJpeg(3000, 2000), 'jpg');

    expect(array_slice(getimagesizefromstring($result->main), 0, 2))->toBe([1620, 1080]);
});
