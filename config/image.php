<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports "libvips", "GD Library" and "Imagick" to
    | process images internally. Depending on your PHP setup, you can choose
    | one of them. libvips is the default: it is significantly faster and uses
    | far less memory than GD/Imagick, and has strong support for modern
    | formats such as WebP and AVIF. It requires the php-vips extension and
    | libvips to be installed (both ship in the official Pixelfed image).
    |
    | Included options:
    |   - vips    = \Intervention\Image\Drivers\Vips\Driver::class
    |   - gd      = \Intervention\Image\Drivers\Gd\Driver::class
    |   - imagick = \Intervention\Image\Drivers\Imagick\Driver::class
    |
    */

    'driver' => env('IMAGE_DRIVER', 'vips'),

    /*
    |--------------------------------------------------------------------------
    | Configuration Options
    |--------------------------------------------------------------------------
    |
    | These options control the behavior of Intervention Image.
    |
    | - "autoOrientation" controls whether an imported image should be
    |    automatically rotated according to any existing Exif data.
    |
    | - "decodeAnimation" decides whether a possibly animated image is
    |    decoded as such or whether the animation is discarded.
    |
    | - "backgroundColor" Defines the default background color.
    |
    | - "strip" controls if meta data like exif tags should be removed when
    |    encoding images.
    */

    'options' => [
        'autoOrientation' => true,
        'decodeAnimation' => true,
        'backgroundColor' => env('IMAGE_BACKGROUNDCOLOR', 'ffffff'),
        'strip' => env('IMAGE_STRIP', true),
    ],
];
