<?php

namespace App\Util\Media;

use App\Models\Media;
use App\Services\StatusService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;

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

        $this->imageManager = ImageDriverManager::createImageManager();
    }

    public function orientations(): array
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

    public function getAspect($width, $height, $isThumbnail): array
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

        // The file this transform is about to supersede. When the output
        // extension differs from what is currently stored (e.g. heic/avif -> jpg,
        // or a thumbnail regenerated to a new extension), the new file lands at
        // a different name and the old one would be orphaned in the media
        // directory. Capture it now so we can delete it after a successful
        // write. For the base image this is media_path; for a thumbnail it is
        // the existing thumbnail_path.
        $previousPath = $thumbnail ? $media->thumbnail_path : $media->media_path;

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

            $img = $this->imageManager->decodeBinary($fileContents);
            $img = $img->orient();

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

            // Remove the file we just superseded when the new output landed at a
            // different path (extension change / thumbnail regeneration), so the
            // old file is not orphaned in the media directory.
            $this->deleteSupersededFile($previousPath, $converted['path'], $localFs);

            $media->save();

            if ($thumbnail) {
                $this->generateBlurhash($media, $encoded->toString());
            }

            if ($media->status_id) {
                Cache::forget('status:transformer:media:attachments:'.$media->status_id);
                Cache::forget('status:thumb:'.$media->status_id);
                StatusService::del($media->status_id);
            }

        } catch (\Throwable $e) {
            // Always logged, never gated behind dev_log: when this fails the
            // untouched original upload keeps being served at full size, and
            // nothing else in the app surfaces that.
            Log::error(sprintf(
                'MediaResizeException: could not %s media id %s (%s) [%s]: %s',
                $thumbnail ? 'thumbnail' : 'resize',
                $media->id,
                $media->mime,
                $e::class,
                $e->getMessage()
            ));
        }
    }

    /**
     * Delete a previous file that a transform has just replaced, but only when
     * the new output landed at a different path (so we never delete the file we
     * just wrote). No-op when there was no previous path or it is unchanged.
     */
    protected function deleteSupersededFile(?string $previousPath, string $newPath, bool $localFs): void
    {
        if (! $previousPath || $previousPath === $newPath) {
            return;
        }

        try {
            if ($localFs) {
                $full = storage_path('app/'.$previousPath);
                if (is_file($full)) {
                    @unlink($full);
                }
            } else {
                $disk = Storage::disk($this->defaultDisk);
                if ($disk->exists($previousPath)) {
                    $disk->delete($previousPath);
                }
            }
        } catch (\Exception $e) {
            if (config('app.dev_log')) {
                Log::info('Superseded media cleanup failed: '.$e->getMessage());
            }
        }
    }

    public function setBaseName($basePath, $thumbnail, $extension): array
    {
        $pathInfo = pathinfo($basePath);
        $dir = isset($pathInfo['dirname']) && $pathInfo['dirname'] !== '.' ? $pathInfo['dirname'].'/' : '';
        $filename = $pathInfo['filename'];
        $name = ($thumbnail == true) ? $filename.'_thumb' : $filename;
        $basePath = $dir.$name.'.'.$extension;

        return ['path' => $basePath, 'png' => false];
    }

    /**
     * Hash the thumbnail that was just encoded. The bytes are already in memory,
     * so they are hashed directly instead of being read back from disk (or pulled
     * back down from cloud storage into a temp file) a moment after being written.
     */
    protected function generateBlurhash($media, ?string $contents = null)
    {
        try {
            if ($contents === null) {
                $contents = $this->defaultDisk === 'local'
                    ? file_get_contents(storage_path('app/'.$media->thumbnail_path))
                    : Storage::disk($this->defaultDisk)->get($media->thumbnail_path);
            }

            $blurhash = $contents ? Blurhash::fromBinary($contents) : null;

            $media->blurhash = $blurhash ?? Blurhash::DEFAULT_HASH;
            $media->save();
        } catch (\Throwable $e) {
            // \Throwable, not \Exception: the blurhash is decorative and must never
            // fail the thumbnail job, which still has to mark the media as processed
            // and dispatch ImageUpdate.
            if (config('app.dev_log')) {
                Log::info('Blurhash generation failed: '.$e->getMessage());
            }
        }
    }
}
