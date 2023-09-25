<?php

namespace App\Services;

use Storage;
use Illuminate\Http\File;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Exception\ConnectException;
use League\Flysystem\UnableToWriteFile;

class ResilientMediaStorageService
{
    static $attempts = 0;

    public static function store($storagePath, $path, $name)
    {
        return (bool) config_cache('pixelfed.cloud_storage') && (bool) config('media.storage.remote.resilient_mode') ?
            self::handleResilientStore($storagePath, $path, $name) :
            self::handleStore($storagePath, $path, $name);
    }

    public static function handleStore($storagePath, $path, $name)
    {
        return retry(3, function() use($storagePath, $path, $name) {
            $baseDisk = (bool) config_cache('pixelfed.cloud_storage') ? config('filesystems.cloud') : 'local';
            $disk = Storage::disk($baseDisk);
            $file = $disk->putFileAs($storagePath, new File($path), $name, 'public');
            return $disk->url($file);
        }, random_int(100, 500));
    }

    public static function handleResilientStore($storagePath, $path, $name)
    {
        $attempts = 0;
        return retry(4, function() use($storagePath, $path, $name, $attempts) {
            self::$attempts++;
            usleep(100000);
            $baseDisk = self::$attempts > 1 ? self::getAltDriver() : config('filesystems.cloud');
            try {
                $disk = Storage::disk($baseDisk);
                $file = $disk->putFileAs($storagePath, new File($path), $name, 'public');
            } catch (S3Exception | ClientException | ConnectException | UnableToWriteFile | Exception $e) {}
            return $disk->url($file);
        }, function (int $attempt, Exception $exception) {
            return $attempt * 200;
        });
    }

    public static function getAltDriver()
    {
        $drivers = [];
        if(config('filesystems.disks.alt-primary.enabled')) {
            $drivers[] = 'alt-primary';
        }
        if(config('filesystems.disks.alt-secondary.enabled')) {
            $drivers[] = 'alt-secondary';
        }
        if(empty($drivers)) {
            return false;
        }
        $key = array_rand($drivers, 1);
        return $drivers[$key];
    }
}
