<?php

namespace App\Util\Media;

use Intervention\Image\ImageManager;

class ImageDriverManager
{
    /**
     * Get the appropriate image driver class based on configuration.
     *
     * @return string
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
     * @param array $options Additional options for ImageManager
     * @return ImageManager
     */
    public static function createImageManager(array $options = []): ImageManager
    {
        $configOptions = config('image.options', []);

        $options = array_merge($configOptions, $options);

        return new ImageManager(
            self::getDriverClass(),
            autoOrientation: (bool) ($options['autoOrientation'] ?? true),
            decodeAnimation: (bool) ($options['decodeAnimation'] ?? true),
            blendingColor: (string) ($options['blendingColor'] ?? 'ffffff'),
            strip: (bool) ($options['strip'] ?? true)
        );
    }
}
