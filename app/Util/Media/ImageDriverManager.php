<?php

namespace App\Util\Media;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageDriverManager
{
    /**
     * Get the appropriate image driver class based on configuration.
     *
     * @param  string|null  $driver  Driver name to resolve instead of the configured one
     */
    public static function getDriverClass(?string $driver = null): string
    {
        return match ($driver ?? config('image.driver')) {
            'gd' => Driver::class,
            'imagick' => \Intervention\Image\Drivers\Imagick\Driver::class,
            'vips' => \Intervention\Image\Drivers\Vips\Driver::class,
            default => Driver::class
        };
    }

    /**
     * Create a new ImageManager instance with the configured driver.
     *
     * @param  array  $options  Additional options for ImageManager
     * @param  string|null  $driver  Driver name to use instead of the configured one
     */
    public static function createImageManager(array $options = [], ?string $driver = null): ImageManager
    {
        $configOptions = config('image.options', []);

        $options = array_merge($configOptions, $options);

        return new ImageManager(
            self::getDriverClass($driver),
            autoOrientation: (bool) ($options['autoOrientation'] ?? true),
            decodeAnimation: (bool) ($options['decodeAnimation'] ?? true),
            backgroundColor: (string) ($options['backgroundColor'] ?? 'ffffff'),
            strip: (bool) ($options['strip'] ?? true)
        );
    }
}
