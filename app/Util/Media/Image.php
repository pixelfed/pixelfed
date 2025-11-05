<?php

namespace App\Util\Media;

use App\Media;
use App\Services\StatusService;
use Cache;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Log;
use Storage;

class Image
{
    public $square;

    public $landscape;

    public $portrait;

    public $thumbnail;

    public $orientation;

    public $acceptedMimes = [
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/webp',
        'image/avif',
        'image/heic',
    ];

    protected $imageManager;

    protected $defaultDisk;

    public function __construct()
    {
        ini_set('memory_limit', config('pixelfed.memory_limit', '1024M'));

        $this->square = $this->orientations()['square'];
        $this->landscape = $this->orientations()['landscape'];
        $this->portrait = $this->orientations()['portrait'];
        $this->thumbnail = [
            'width' => 640,
            'height' => 640,
        ];
        $this->orientation = null;

        $this->defaultDisk = config('filesystems.default');

        $driver = match (config('image.driver')) {
            'imagick' => \Intervention\Image\Drivers\Imagick\Driver::class,
            'vips' => \Intervention\Image\Drivers\Vips\Driver::class,
            default => \Intervention\Image\Drivers\Gd\Driver::class
        };

        $this->imageManager = new ImageManager(
            $driver,
            autoOrientation: true,
            decodeAnimation: true,
            blendingColor: 'ffffff',
            strip: true
        );
    }

    public function orientations()
    {
        return [
            'square' => [
                'width' => 1080,
                'height' => 1080,
            ],
            'landscape' => [
                'width' => 1920,
                'height' => 1080,
            ],
            'portrait' => [
                'width' => 1080,
                'height' => 1350,
            ],
        ];
    }

    public function getAspect($width, $height, $isThumbnail)
    {
        if ($isThumbnail) {
            return [
                'dimensions' => $this->thumbnail,
                'orientation' => 'thumbnail',
            ];
        }

        $aspect = $width / $height;
        $orientation = $aspect === 1 ? 'square' :
        ($aspect > 1 ? 'landscape' : 'portrait');
        $this->orientation = $orientation;

        return [
            'dimensions' => $this->orientations()[$orientation],
            'orientation' => $orientation,
            'width_original' => $width,
            'height_original' => $height,
        ];
    }

    public function resizeImage(Media $media)
    {
        $this->handleResizeImage($media);
    }

    public function resizeThumbnail(Media $media)
    {
        $this->handleThumbnailImage($media);
    }

    public function handleResizeImage(Media $media)
    {
        $this->handleImageTransform($media, false);
    }

    public function handleThumbnailImage(Media $media)
    {
        $this->handleImageTransform($media, true);
    }

    public function handleImageTransform(Media $media, $thumbnail = false)
    {
        $path = $media->media_path;
        $localFs = config('filesystems.default') === 'local';

        if (! in_array($media->mime, $this->acceptedMimes)) {
            return;
        }

        try {
            $fileContents = null;
            $tempFile = null;

            if ($this->defaultDisk === 'local') {
                $filePath = storage_path('app/'.$path);
                $fileContents = file_get_contents($filePath);
            } else {
                $fileContents = Storage::disk($this->defaultDisk)->get($path);
            }

            $fileInfo = pathinfo($path);
            $extension = strtolower($fileInfo['extension'] ?? 'jpg');
            $outputExtension = $extension;

            $metadata = null;
            if (! $thumbnail && config('media.exif.database', false) == true) {
                try {
                    if ($this->defaultDisk !== 'local') {
                        $tempFile = tempnam(sys_get_temp_dir(), 'exif_');
                        file_put_contents($tempFile, $fileContents);
                        $exifPath = $tempFile;
                    } else {
                        $exifPath = storage_path('app/'.$path);
                    }

                    $exif = @exif_read_data($exifPath);

                    if ($exif) {
                        $meta = [];
                        $keys = [
                            'FileName',
                            'FileSize',
                            'FileType',
                            'Make',
                            'Model',
                            'MimeType',
                            'ColorSpace',
                            'ExifVersion',
                            'Orientation',
                            'UserComment',
                            'XResolution',
                            'YResolution',
                            'FileDateTime',
                            'SectionsFound',
                            'ExifImageWidth',
                            'ResolutionUnit',
                            'ExifImageLength',
                            'FlashPixVersion',
                            'Exif_IFD_Pointer',
                            'YCbCrPositioning',
                            'ComponentsConfiguration',
                            'ExposureTime',
                            'FNumber',
                            'ISOSpeedRatings',
                            'ShutterSpeedValue',
                        ];
                        foreach ($exif as $k => $v) {
                            if (in_array($k, $keys)) {
                                $meta[$k] = $v;
                            }
                        }
                        $media->metadata = json_encode($meta);
                    }

                    if ($tempFile && file_exists($tempFile)) {
                        unlink($tempFile);
                        $tempFile = null;
                    }
                } catch (\Exception $e) {
                    if ($tempFile && file_exists($tempFile)) {
                        unlink($tempFile);
                    }
                    if (config('app.dev_log')) {
                        Log::info('EXIF extraction failed: '.$e->getMessage());
                    }
                }
            }

            $img = $this->imageManager->read($fileContents);

            $ratio = $this->getAspect($img->width(), $img->height(), $thumbnail);
            $aspect = $ratio['dimensions'];
            $orientation = $ratio['orientation'];

            if ($thumbnail) {
                $img = $img->coverDown(
                    $aspect['width'],
                    $aspect['height']
                );
            } else {
                if (
                    ($ratio['width_original'] > $aspect['width'])
                    || ($ratio['height_original'] > $aspect['height'])
                ) {
                    $img = $img->scaleDown(
                        $aspect['width'],
                        $aspect['height']
                    );
                }
            }

            $quality = config_cache('pixelfed.image_quality');

            $encoder = null;
            switch ($extension) {
                case 'jpeg':
                case 'jpg':
                    $encoder = new JpegEncoder($quality);
                    $outputExtension = 'jpg';
                    break;
                case 'png':
                    $encoder = new PngEncoder;
                    $outputExtension = 'png';
                    break;
                case 'webp':
                    $encoder = new WebpEncoder($quality);
                    $outputExtension = 'webp';
                    break;
                case 'avif':
                    $encoder = new JpegEncoder($quality);
                    $outputExtension = 'jpg';
                    break;
                case 'heic':
                    $encoder = new JpegEncoder($quality);
                    $outputExtension = 'jpg';
                    break;
                default:
                    $encoder = new JpegEncoder($quality);
                    $outputExtension = 'jpg';
            }

            $converted = $this->setBaseName($path, $thumbnail, $outputExtension);
            $encoded = $encoder->encode($img);

            if ($localFs) {
                $newPath = storage_path('app/'.$converted['path']);
                file_put_contents($newPath, $encoded->toString());
            } else {
                Storage::disk($this->defaultDisk)->put(
                    $converted['path'],
                    $encoded->toString()
                );
            }

            if ($thumbnail == true) {
                $media->thumbnail_path = $converted['path'];
                $media->thumbnail_url = url(Storage::url($converted['path']));
            } else {
                $media->width = $img->width();
                $media->height = $img->height();
                $media->orientation = $orientation;
                $media->media_path = $converted['path'];
                $media->mime = 'image/'.$outputExtension;
            }

            $media->save();

            if ($thumbnail) {
                $this->generateBlurhash($media);
            }

            if ($media->status_id) {
                Cache::forget('status:transformer:media:attachments:'.$media->status_id);
                Cache::forget('status:thumb:'.$media->status_id);
                StatusService::del($media->status_id);
            }

        } catch (\Exception $e) {
            $media->processed_at = now();
            $media->save();
            if (config('app.dev_log')) {
                Log::info('MediaResizeException: '.$e->getMessage().' | Could not process media id: '.$media->id);
            }
        }
    }

    public function setBaseName($basePath, $thumbnail, $extension)
    {
        $path = explode('.', $basePath);
        $name = ($thumbnail == true) ? $path[0].'_thumb' : $path[0];
        $basePath = "{$name}.{$extension}";

        return ['path' => $basePath, 'png' => false];
    }

    protected function generateBlurhash($media)
    {
        try {
            if ($this->defaultDisk === 'local') {
                $thumbnailPath = storage_path('app/'.$media->thumbnail_path);
                $blurhash = Blurhash::generate($media, $thumbnailPath);
            } else {
                $tempFile = tempnam(sys_get_temp_dir(), 'blurhash_');
                $contents = Storage::disk($this->defaultDisk)->get($media->thumbnail_path);
                file_put_contents($tempFile, $contents);

                $blurhash = Blurhash::generate($media, $tempFile);

                unlink($tempFile);
            }

            if ($blurhash) {
                $media->blurhash = $blurhash;
                $media->save();
            }
        } catch (\Exception $e) {
            if (config('app.dev_log')) {
                Log::info('Blurhash generation failed: '.$e->getMessage());
            }
        }
    }
}
