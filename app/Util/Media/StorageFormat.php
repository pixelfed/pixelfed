<?php

namespace App\Util\Media;


enum StorageFormat: string
{
    case JPEG = 'jpeg';
    case JXL = 'jxl';

    public function extension(): string
    {
        return match($this) {
            self::JPEG => 'jpg',
            self::JXL => 'jxl',
        };
    }

    /**
     * Get the appropriate MIME type for the format.
     */
    public function mimeType(): string
    {
        return match($this) {
            self::JPEG => 'image/jpeg',
            self::JXL => 'image/jxl',
        };
    }

    public function saveBuffer(\Jcupitt\Vips\Image $media): string
    {
        return match($this) {
            self::JPEG => $media->uhdrsave_buffer(),
            self::JXL => $media->jxlsave_buffer(),
        };
    }

    static function getConfigured(): StorageFormat
    {
        $key = config_cache("pixelfed.image_storage_format", 'jpeg');
        return StorageFormat::from($key);
    }
}
