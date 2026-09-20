<?php

namespace App\Util\Media;

use App\Models\Media;
use App\Util\Blurhash\Blurhash as BlurhashEngine;
use GdImage;
use Imagick;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;
use RuntimeException;
use Throwable;

class Blurhash
{
    const DEFAULT_HASH = 'U4Rfzst8?bt7ogayj[j[~pfQ9Goe%Mj[WBay';

    // Long edge, in pixels, of the sample the hash is computed from.
    //
    // The output is a 4x4-component DCT, so sampling the source at full resolution
    // buys almost nothing, while the per-pixel PHP arrays the encoder needs cost a
    // few hundred bytes each: a 720x1280 frame measured at 224 MB peak, which is
    // what used to kill workers on full resolution video thumbnails (pixelfed#2652).
    //
    // 128px measured as the point of diminishing returns: against the full
    // resolution hash, mean per-channel deviation of the decoded 24x24 preview is
    // ~7.5/255 at a 32px sample, ~4.5/255 at 64px, ~2.5/255 at 128px, and no better
    // at 256px. At 128px a 1920x1080 frame samples 9,216 pixels instead of 2,073,600.
    const SAMPLE_MAX = 128;

    const COMPONENTS_X = 4;

    const COMPONENTS_Y = 4;

    // Mime types of the media a thumbnail is hashed for. The thumbnail itself keeps
    // the source format (png, jpg, webp), with avif/heic sources landing as jpg.
    const SUPPORTED_MIMES = [
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/webp',
        'video/mp4',
    ];

    public static function generate(Media $media, $path = false): string
    {
        if (! in_array($media->mime, self::SUPPORTED_MIMES)) {
            return self::DEFAULT_HASH;
        }

        if ($media->thumbnail_path == null) {
            return self::DEFAULT_HASH;
        }

        $file = $path ?: storage_path('app/'.$media->thumbnail_path);

        if (! is_file($file)) {
            return self::DEFAULT_HASH;
        }

        $contents = file_get_contents($file);
        if ($contents === false || $contents === '') {
            return self::DEFAULT_HASH;
        }

        return self::fromBinary($contents) ?? self::DEFAULT_HASH;
    }

    /**
     * Hash raw image bytes, or return null when they cannot be decoded.
     *
     * Decoding goes through the configured Intervention driver instead of calling
     * GD directly, for two reasons. A vips (or imagick) host is not required to
     * ship ext-gd at all, and there the old imagecreatefromstring() call was an
     * undefined function: an \Error, which slipped past every catch (\Exception)
     * above it and failed the thumbnail job. And the driver that wrote the
     * thumbnail is the one guaranteed to be able to read it back (webp included).
     *
     * GD stays as a fallback when it is installed, so a misconfigured driver
     * degrades to the previous behaviour instead of to the default hash.
     */
    public static function fromBinary(string $contents): ?string
    {
        foreach (self::candidateDrivers() as $driver) {
            try {
                $pixels = self::samplePixels($contents, $driver);
                if (! $pixels) {
                    continue;
                }

                $blurhash = BlurhashEngine::encode($pixels, self::COMPONENTS_X, self::COMPONENTS_Y);

                return strlen($blurhash) > 191 ? null : $blurhash;
            } catch (Throwable $e) {
                continue;
            }
        }

        return null;
    }

    protected static function candidateDrivers(): array
    {
        $drivers = [config('image.driver', 'vips')];

        if (function_exists('imagecreatefromstring')) {
            $drivers[] = 'gd';
        }

        return array_values(array_unique($drivers));
    }

    /**
     * Decode, downscale and flatten the image, then return it as rows of [r, g, b].
     */
    protected static function samplePixels(string $contents, string $driver): array
    {
        $image = ImageDriverManager::createImageManager([
            'decodeAnimation' => false,
        ], $driver)->decodeBinary($contents);

        $image = $image->scaleDown(self::SAMPLE_MAX, self::SAMPLE_MAX);

        // Transparent pixels have to be composited onto the background before they
        // are read, because only RGB is sampled. GD happened to leave white behind
        // fully transparent pixels after a resize, libvips leaves black, so without
        // this every transparent PNG hashes to a dark placeholder under vips.
        $image = $image->fillTransparentAreas();

        $native = $image->core()->native();

        return match (true) {
            $native instanceof VipsImage => self::pixelsFromVips($native),
            $native instanceof Imagick => self::pixelsFromImagick($native),
            $native instanceof GdImage => self::pixelsFromGd($native),
            default => throw new RuntimeException('Unsupported image driver for blurhash'),
        };
    }

    protected static function pixelsFromVips(VipsImage $image): array
    {
        // The vips driver keeps the source colourspace (a CMYK jpg thumbnail stays
        // CMYK), so normalise to 8-bit sRGB before reading the first three bands.
        if ($image->interpretation !== Interpretation::SRGB) {
            $image = $image->colourspace(Interpretation::SRGB);
        }

        // Already opaque after fillTransparentAreas(), this only drops the alpha band.
        if ($image->hasAlpha()) {
            $image = $image->flatten(['background' => [255, 255, 255]]);
        }

        if ($image->bands > 3) {
            $image = $image->extract_band(0, ['n' => 3]);
        }

        if ($image->format !== BandFormat::UCHAR) {
            $image = $image->cast(BandFormat::UCHAR);
        }

        $width = $image->width;
        $height = $image->height;
        $bands = $image->bands;

        return self::pixelsFromBytes($image->writeToMemory(), $width, $height, $bands);
    }

    protected static function pixelsFromImagick(Imagick $image): array
    {
        if ($image->getImageColorspace() !== Imagick::COLORSPACE_SRGB) {
            $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
        }

        $width = $image->getImageWidth();
        $height = $image->getImageHeight();
        $values = $image->exportImagePixels(0, 0, $width, $height, 'RGB', Imagick::PIXEL_CHAR);

        $pixels = [];
        $i = 0;
        for ($y = 0; $y < $height; $y++) {
            $row = [];
            for ($x = 0; $x < $width; $x++) {
                $row[] = [$values[$i], $values[$i + 1], $values[$i + 2]];
                $i += 3;
            }
            $pixels[] = $row;
        }

        return $pixels;
    }

    protected static function pixelsFromGd(GdImage $image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $pixels = [];
        for ($y = 0; $y < $height; $y++) {
            $row = [];
            for ($x = 0; $x < $width; $x++) {
                $colors = imagecolorsforindex($image, imagecolorat($image, $x, $y));

                $row[] = [$colors['red'], $colors['green'], $colors['blue']];
            }
            $pixels[] = $row;
        }

        return $pixels;
    }

    protected static function pixelsFromBytes(string $bytes, int $width, int $height, int $bands): array
    {
        if ($width < 1 || $height < 1 || strlen($bytes) < $width * $height * $bands) {
            throw new RuntimeException('Unexpected pixel buffer size for blurhash');
        }

        $pixels = [];
        $i = 0;
        for ($y = 0; $y < $height; $y++) {
            $row = [];
            for ($x = 0; $x < $width; $x++) {
                $r = ord($bytes[$i]);
                $row[] = $bands >= 3
                    ? [$r, ord($bytes[$i + 1]), ord($bytes[$i + 2])]
                    : [$r, $r, $r];
                $i += $bands;
            }
            $pixels[] = $row;
        }

        return $pixels;
    }
}
