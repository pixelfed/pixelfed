<?php

namespace App\Services\Media;

use App\Util\Media\Blurhash;
use App\Util\Media\ImageDriverManager;
use Closure;
use Intervention\Image\Drivers\Vips\Core as VipsCore;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ProfileInterface;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;
use Throwable;

class ImageProcessor
{
    public const string MODE_FULL = 'full';

    public const string MODE_THUMBNAIL = 'thumbnail';

    public const int THUMBNAIL_SIZE = 640;

    public const array BOXES = [
        'square' => [1080, 1080],
        'landscape' => [1920, 1080],
        'portrait' => [1080, 1350],
    ];

    public const array OPTIMIZABLE = ['jpg', 'png', 'webp'];

    protected static ?bool $palette = null;

    public function __construct(
        protected ImageManager $manager,
        protected int $quality = 80,
        protected bool $resize = true,
        protected ?Closure $optimizer = null,
    ) {}

    public static function make(): self
    {
        $quality = (int) config_cache('pixelfed.image_quality');

        return new self(
            ImageDriverManager::createImageManager(),
            $quality >= 1 && $quality <= 100 ? $quality : 80,
            (bool) config_cache('pixelfed.optimize_image'),
            fn (string $path) => ImageOptimizer::optimize($path),
        );
    }

    public static function outputExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'png' => 'png',
            'webp' => 'webp',
            default => 'jpg',
        };
    }

    public static function orientation(int $width, int $height): string
    {
        if ($width === $height) {
            return 'square';
        }

        return $width > $height ? 'landscape' : 'portrait';
    }

    public function process(string $path, string $extension, string $mode = self::MODE_FULL): ProcessedImage
    {
        $image = $this->manager->decodePath($path);
        $profile = $this->profile($image);
        $originalWidth = $image->width();
        $originalHeight = $image->height();
        $orientation = self::orientation($originalWidth, $originalHeight);
        $extension = self::outputExtension($extension);
        $optimize = $this->resize
            && $this->optimizer !== null
            && ! $image->core() instanceof VipsCore
            && in_array($extension, self::OPTIMIZABLE, true);

        $main = null;

        if ($mode === self::MODE_FULL && $this->resize) {
            [$boxWidth, $boxHeight] = self::BOXES[$orientation];

            if ($originalWidth > $boxWidth || $originalHeight > $boxHeight) {
                $image->scaleDown($boxWidth, $boxHeight);
            }

            $this->settle($image);
            $thumbnail = $this->settle((clone $image)->coverDown(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE));
            $main = $this->encode($image, $extension, $profile, $optimize);
        } else {
            $thumbnail = $this->settle($image->coverDown(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE));
        }

        return new ProcessedImage(
            thumbnail: $this->encode($thumbnail, $extension, $profile, $optimize),
            extension: $extension,
            width: $main !== null ? $image->width() : $originalWidth,
            height: $main !== null ? $image->height() : $originalHeight,
            originalWidth: $originalWidth,
            originalHeight: $originalHeight,
            orientation: $orientation,
            main: $main,
            blurhash: Blurhash::fromImage($thumbnail),
        );
    }

    protected function encode(ImageInterface $image, string $extension, ?ProfileInterface $profile, bool $optimize): string
    {
        $this->applyProfile($image, $profile);

        $bytes = match ($extension) {
            'png' => $this->encodePng($image),
            'webp' => $image->encode(new WebpEncoder($this->quality))->toString(),
            default => $image->encode(new JpegEncoder($this->quality, progressive: true))->toString(),
        };

        return $optimize ? $this->optimize($bytes) : $bytes;
    }

    protected function encodePng(ImageInterface $image): string
    {
        if (! $image->core() instanceof VipsCore || self::$palette === false) {
            return $image->encode(new PngEncoder)->toString();
        }

        try {
            $bytes = $image->encode(new PngEncoder(indexed: true))->toString();
            self::$palette = true;

            return $bytes;
        } catch (Throwable) {
            self::$palette = false;

            return $image->encode(new PngEncoder)->toString();
        }
    }

    protected function optimize(string $bytes): string
    {
        if ($this->optimizer === null) {
            return $bytes;
        }

        $path = tempnam(sys_get_temp_dir(), 'pf_opt_');

        if ($path === false) {
            return $bytes;
        }

        try {
            file_put_contents($path, $bytes);
            ($this->optimizer)($path);
            clearstatcache(true, $path);
            $optimized = file_get_contents($path);

            return is_string($optimized) && $optimized !== '' ? $optimized : $bytes;
        } catch (Throwable) {
            return $bytes;
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    protected function profile(ImageInterface $image): ?ProfileInterface
    {
        try {
            return $image->profile();
        } catch (Throwable) {
            return null;
        }
    }

    protected function applyProfile(ImageInterface $image, ?ProfileInterface $profile): void
    {
        if ($profile === null) {
            return;
        }

        try {
            $image->setProfile($profile);
        } catch (Throwable) {
            return;
        }
    }

    protected function settle(ImageInterface $image): ImageInterface
    {
        $core = $image->core();

        if ($core instanceof VipsCore) {
            VipsCore::ensureInMemory($core);
        }

        return $image;
    }
}
