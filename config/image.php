<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports “GD Library” and “Imagick” to process images
    | internally. Depending on your PHP setup, you can choose one of them.
    |
    | Included options:
    |   - gd      = \Intervention\Image\Drivers\Gd\Driver::class
    |   - imagick = \Intervention\Image\Drivers\Imagick\Driver::class
    |   - vips    = \Intervention\Image\Drivers\vips\Driver::class
    |
    */

    'driver' => env('IMAGE_DRIVER', 'gd'),

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
    | - "blendingColor" Defines the default blending color.
    |
    | - "strip" controls if meta data like exif tags should be removed when
    |    encoding images.
    */

    'options' => [
        'autoOrientation' => true,
        'decodeAnimation' => true,
        'blendingColor' => env('IMAGE_BLENDINGCOLOR', 'ffffff'),
        'strip' => env('IMAGE_STRIP', true),
    ]
];
