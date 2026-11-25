<?php

namespace App\Jobs\MediaPipeline;

use App\Models\Media;
use App\Services\ContentCredentials\ContentCredentialService;
use App\Services\ContentCredentials\ManifestSummary;
use App\Services\Media\ImageProcessor;
use App\Services\Media\ProcessedImage;
use App\Services\StatusService;
use App\Support\MemoryLimit;
use App\Util\Media\Blurhash;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessImage implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const array ACCEPTED_MIMES = [
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/webp',
        'image/avif',
        'image/heic',
    ];

    public const array EXIF_KEYS = [
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

    public $tries = 3;

    public $backoff = [10, 60];

    public $uniqueFor = 3600;

    public $deleteWhenMissingModels = true;

    public function __construct(
        public Media $media,
        public string $mode = ImageProcessor::MODE_FULL,
        public bool $force = false,
    ) {
        $this->onQueue('mmo');
    }

    public function uniqueId(): string
    {
        return implode(':', [$this->media->id, $this->mode, $this->force ? 'force' : 'auto']);
    }

    public function handle(): void
    {
        $media = $this->media;

        if (! $media->media_path || $media->remote_media || $media->skip_optimize) {
            return;
        }

        if ($this->mode === ImageProcessor::MODE_FULL && $media->processed_at && ! $this->force) {
            return;
        }

        if (! in_array($media->mime, self::ACCEPTED_MIMES, true)) {
            $this->complete($media);

            return;
        }

        $source = $this->localCopy($media);

        if ($source === null) {
            Log::error("ProcessImage: media {$media->id} source file missing at {$media->media_path}, cannot process");
            $media->skip_optimize = true;
            $media->save();

            return;
        }

        [$path, $temporary] = $source;

        try {
            $this->process($media, $path);
        } finally {
            if ($temporary && is_file($path)) {
                unlink($path);
            }
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::error(sprintf(
            'ProcessImage: media %s failed after retries [%s]: %s',
            $this->media->id,
            $e ? $e::class : 'unknown',
            $e?->getMessage() ?? ''
        ));

        $media = $this->media->fresh();

        if ($media && ! $media->processed_at) {
            $this->complete($media);
        }
    }

    protected function process(Media $media, string $path): void
    {
        MemoryLimit::raise(config('pixelfed.memory_limit', '1024M'));

        $full = $this->mode === ImageProcessor::MODE_FULL;
        $sourceMime = $media->mime;
        $sourceBytes = $full && ContentCredentialService::enabled() ? file_get_contents($path) : false;
        $credentials = is_string($sourceBytes) ? ContentCredentialService::inspect($media, $sourceBytes) : null;

        try {
            $processed = ImageProcessor::make()->process(
                $path,
                pathinfo($media->media_path, PATHINFO_EXTENSION) ?: 'jpg',
                $this->mode
            );
        } catch (Throwable $e) {
            Log::error(sprintf(
                'ProcessImage: media %s (%s) could not be processed, keeping the original [%s]: %s',
                $media->id,
                $media->mime,
                $e::class,
                $e->getMessage()
            ));

            $this->complete($media);

            return;
        }

        $disk = (string) config('filesystems.default');
        $previousMain = $media->media_path;
        $previousThumbnail = $media->thumbnail_path;
        $thumbnailPath = $this->derivativePath($previousMain, $processed->extension, true);
        $mainSize = (int) filesize($path);

        if ($processed->main !== null) {
            $mainPath = $this->derivativePath($previousMain, $processed->extension);
            $main = $this->sign($media, $processed, $mainPath, $credentials, $sourceBytes, $sourceMime);

            $this->put($disk, $mainPath, $main);

            $media->media_path = $mainPath;
            $media->mime = 'image/'.$processed->extension;
            $mainSize = strlen($main);
        }

        $this->put($disk, $thumbnailPath, $processed->thumbnail);

        if ($full) {
            $media->width = $processed->width;
            $media->height = $processed->height;
            $media->orientation = $processed->orientation;

            if (config('media.exif.database', false) == true) {
                $media->metadata = $this->exifMetadata($path) ?? $media->metadata;
            }
        }

        $media->thumbnail_path = $thumbnailPath;
        $media->thumbnail_url = url(Storage::url($thumbnailPath));
        $media->blurhash = $processed->blurhash ?? Blurhash::DEFAULT_HASH;
        $media->size = $mainSize + strlen($processed->thumbnail);
        $media->processed_at = now();
        $media->save();

        $this->deleteSuperseded($disk, $previousMain, $media->media_path);
        $this->deleteSuperseded($disk, $previousThumbnail, $thumbnailPath);
        $this->flushStatus($media);

        MediaStoragePipeline::dispatch($media);
    }

    protected function sign(
        Media $media,
        ProcessedImage $processed,
        string $mainPath,
        ?ManifestSummary $credentials,
        string|false $sourceBytes,
        string $sourceMime,
    ): string {
        $main = (string) $processed->main;

        if (! is_string($sourceBytes)) {
            return $main;
        }

        return ContentCredentialService::signDerivative(
            $media,
            $credentials,
            $main,
            $processed->mime(),
            basename($mainPath),
            $sourceBytes,
            $sourceMime,
            $processed->resized(),
        ) ?? $main;
    }

    protected function complete(Media $media): void
    {
        $media->processed_at = now();
        $media->save();

        MediaStoragePipeline::dispatch($media);
    }

    protected function localCopy(Media $media): ?array
    {
        $disk = (string) config('filesystems.default');

        if ($disk === 'local') {
            $path = storage_path('app/'.$media->media_path);

            return is_file($path) ? [$path, false] : null;
        }

        $storage = Storage::disk($disk);

        if (! $storage->exists($media->media_path)) {
            return null;
        }

        $stream = $storage->readStream($media->media_path);

        if (! is_resource($stream)) {
            throw new RuntimeException("ProcessImage: could not read {$media->media_path} from the {$disk} disk");
        }

        $path = tempnam(sys_get_temp_dir(), 'pf_img_');
        $target = $path === false ? false : fopen($path, 'wb');

        if ($path === false || $target === false) {
            fclose($stream);

            throw new RuntimeException('ProcessImage: could not create a temporary file');
        }

        stream_copy_to_stream($stream, $target);
        fclose($stream);
        fclose($target);

        return [$path, true];
    }

    protected function put(string $disk, string $path, string $bytes): void
    {
        $written = $disk === 'local'
            ? file_put_contents(storage_path('app/'.$path), $bytes) !== false
            : Storage::disk($disk)->put($path, $bytes);

        if (! $written) {
            throw new RuntimeException("ProcessImage: could not write {$path} to the {$disk} disk");
        }
    }

    protected function derivativePath(string $path, string $extension, bool $thumbnail = false): string
    {
        $info = pathinfo($path);
        $dir = isset($info['dirname']) && $info['dirname'] !== '.' ? $info['dirname'].'/' : '';

        return $dir.$info['filename'].($thumbnail ? '_thumb' : '').'.'.$extension;
    }

    protected function deleteSuperseded(string $disk, ?string $previous, ?string $current): void
    {
        if (! $previous || $previous === $current) {
            return;
        }

        try {
            if ($disk === 'local') {
                $full = storage_path('app/'.$previous);

                if (is_file($full)) {
                    unlink($full);
                }

                return;
            }

            $storage = Storage::disk($disk);

            if ($storage->exists($previous)) {
                $storage->delete($previous);
            }
        } catch (Throwable $e) {
            Log::warning("ProcessImage: could not remove superseded file {$previous}: ".$e->getMessage());
        }
    }

    protected function flushStatus(Media $media): void
    {
        if (! $media->status_id) {
            return;
        }

        Cache::forget('status:transformer:media:attachments:'.$media->status_id);
        Cache::forget('status:thumb:'.$media->status_id);
        StatusService::del($media->status_id);
    }

    protected function exifMetadata(string $path): ?string
    {
        if (! function_exists('exif_read_data')) {
            return null;
        }

        $exif = @exif_read_data($path);

        if (! is_array($exif)) {
            return null;
        }

        $metadata = json_encode(
            array_intersect_key($exif, array_flip(self::EXIF_KEYS)),
            JSON_INVALID_UTF8_SUBSTITUTE
        );

        return $metadata === false ? null : $metadata;
    }
}
