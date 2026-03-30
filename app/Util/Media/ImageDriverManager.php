<?php

namespace App\Util\Media;

use Intervention\Image\ImageManager;

class ImageDriverManager
{
    /**
     * Get the appropriate image driver class based on configuration.
     */
    public static function getDriverClass(): string
    {
        return match (config('image.driver')) {
            'gd' => \Intervention\Image\Drivers\Gd\Driver::class,
            'imagick' => \Intervention\Image\Drivers\Imagick\Driver::class,
            'vips' => \Intervention\Image\Drivers\Vips\Driver::class,
            default => \Intervention\Image\Drivers\Gd\Driver::class
        };
    }

    /**
     * Create a new ImageManager instance with the configured driver.
     *
     * @param  array  $options  Additional options for ImageManager
     */
    public static function createImageManager(array $options = []): ImageManager
    {
        $configOptions = config('image.options', []);

        $options = array_merge($configOptions, $options);

        return new ImageManager(
            self::getDriverClass(),
            autoOrientation: (bool) ($options['autoOrientation'] ?? true),
            decodeAnimation: (bool) ($options['decodeAnimation'] ?? true),
            backgroundColor: (string) ($options['backgroundColor'] ?? 'ffffff'),
            strip: (bool) ($options['strip'] ?? true)
        );
    }
}
