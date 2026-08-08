<?php

namespace App\Util\Media;

use App\Media;
use App\Util\Blurhash\Blurhash as BlurhashEngine;

class Blurhash
{
    const DEFAULT_HASH = 'U4Rfzst8?bt7ogayj[j[~pfQ9Goe%Mj[WBay';

    public static function generate(Media $media, $path = false)
    {
        if (! in_array($media->mime, ['image/png', 'image/jpeg', 'image/jpg', 'video/mp4'])) {
            return self::DEFAULT_HASH;
        }

        if ($media->thumbnail_path == null) {
            return self::DEFAULT_HASH;
        }

        if ($path) {
            $file = $path;
        } else {
            $localFs = config('filesystems.default') === 'local';
            $file = storage_path('app/'.$media->thumbnail_path);
        }

        if (! is_file($file)) {
            return self::DEFAULT_HASH;
        }

        $image = imagecreatefromstring(file_get_contents($file));
        if (! $image) {
            return self::DEFAULT_HASH;
        }
        $width = imagesx($image);
        $height = imagesy($image);

        // The loop below allocates one PHP array per pixel, which costs a few hundred
        // bytes each once the hashtable is counted: a 720x1280 frame measured at 224 MB
        // peak, i.e. ~255 bytes per pixel, which puts 1920x1080 near half a gigabyte
        // (#2652 reports over a gigabyte on an older PHP). Image thumbnails are capped
        // at 640x640 and run under Image::__construct()'s ini_set('memory_limit',
        // '1024M'), so they stay inside it. Video thumbnails come straight out of
        // FFmpeg at source resolution with no
        // such raise, and blow memory_limit outright — a PHP fatal, which is not an
        // \Exception, so it kills the worker instead of being caught (pixelfed#2652).
        //
        // The output is a 4x4-component DCT, so sampling the source at full resolution
        // buys almost nothing. Downscaling first removes the ceiling for every caller
        // rather than moving it, which is all raising memory_limit would have done.
        //
        // 128px on the long edge measured as the point of diminishing returns: against
        // the full-resolution hash, mean per-channel deviation of the decoded 24x24
        // preview is ~7.5/255 at a 32px sample, ~4.5/255 at 64px, ~2.5/255 at 128px,
        // and no better at 256px. At 128px a 1920x1080 frame samples 9,216 pixels
        // instead of 2,073,600.
        $sampleMax = 128;
        if ($width > $sampleMax || $height > $sampleMax) {
            $scale = $sampleMax / max($width, $height);
            $sampleWidth = max(1, (int) round($width * $scale));
            $sampleHeight = max(1, (int) round($height * $scale));

            $resized = imagescale($image, $sampleWidth, $sampleHeight);
            if ($resized !== false) {
                imagedestroy($image);
                $image = $resized;
                $width = $sampleWidth;
                $height = $sampleHeight;
            }
        }

        $pixels = [];
        for ($y = 0; $y < $height; $y++) {
            $row = [];
            for ($x = 0; $x < $width; $x++) {
                $index = imagecolorat($image, $x, $y);
                $colors = imagecolorsforindex($image, $index);

                $row[] = [$colors['red'], $colors['green'], $colors['blue']];
            }
            $pixels[] = $row;
        }

        imagedestroy($image);

        $components_x = 4;
        $components_y = 4;
        $blurhash = BlurhashEngine::encode($pixels, $components_x, $components_y);
        if (strlen($blurhash) > 191) {
            return self::DEFAULT_HASH;
        }

        return $blurhash;
    }
}
