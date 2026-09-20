<?php

namespace App\Util\Media;

use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

class ImageDriverManager
{
    /**
     * Configured driver name => driver name that passed its health check in
     * this process. Driver construction runs a health check (the vips driver
     * boots FFI + libvips), so the result is memoized per worker.
     *
     * @var array<string, string>
     */
    protected static array $resolved = [];

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
     * Create a new ImageManager instance.
     *
     * With an explicit $driver the call is strict and throws when that driver
     * is unavailable. Without one, the configured driver is tried first and,
     * if its runtime is missing (no libvips, FFI disabled, no ext-imagick...),
     * the next available driver is used and the failure is logged. A host that
     * cannot load the configured driver must degrade to a slower driver, not
     * silently stop resizing uploads.
     *
     * @param  array  $options  Additional options for ImageManager
     * @param  string|null  $driver  Driver name to use instead of the configured one
     */
    public static function createImageManager(array $options = [], ?string $driver = null): ImageManager
    {
        $options = array_merge(config('image.options', []), $options);

        if ($driver !== null) {
            return self::build($driver, $options);
        }

        $configured = (string) config('image.driver', 'vips');

        if (isset(self::$resolved[$configured])) {
            return self::build(self::$resolved[$configured], $options);
        }

        $failure = null;

        foreach (self::candidateDrivers($configured) as $candidate) {
            try {
                $manager = self::build($candidate, $options);
            } catch (Throwable $e) {
                $failure ??= $e;

                continue;
            }

            self::$resolved[$configured] = $candidate;

            if ($candidate !== $configured && $failure) {
                Log::error(sprintf(
                    'Image driver "%s" is unavailable, falling back to "%s". Uploads are still processed, but fix the driver or set IMAGE_DRIVER=%s. Reason: %s',
                    $configured,
                    $candidate,
                    $candidate,
                    self::describe($failure)
                ));
            }

            return $manager;
        }

        throw $failure;
    }

    /**
     * Drivers to try, in order: the configured one, then whatever else this
     * PHP build can actually run.
     *
     * @return array<int, string>
     */
    public static function candidateDrivers(?string $configured = null): array
    {
        $drivers = [$configured ?? (string) config('image.driver', 'vips')];

        if (extension_loaded('imagick')) {
            $drivers[] = 'imagick';
        }

        if (extension_loaded('gd')) {
            $drivers[] = 'gd';
        }

        return array_values(array_unique($drivers));
    }

    /**
     * Forget memoized driver resolution (tests, or after fixing a driver
     * without restarting the worker).
     */
    public static function flush(): void
    {
        self::$resolved = [];
    }

    protected static function build(string $driver, array $options): ImageManager
    {
        return new ImageManager(
            self::getDriverClass($driver),
            autoOrientation: (bool) ($options['autoOrientation'] ?? true),
            decodeAnimation: (bool) ($options['decodeAnimation'] ?? true),
            backgroundColor: (string) ($options['backgroundColor'] ?? 'ffffff'),
            strip: (bool) ($options['strip'] ?? true)
        );
    }

    protected static function describe(Throwable $e): string
    {
        $messages = [];

        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            $messages[] = class_basename($current).': '.$current->getMessage();
        }

        return implode(' <- ', $messages);
    }
}
