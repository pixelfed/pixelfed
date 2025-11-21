<?php

namespace App\Util\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SafeDeleteDirectory
{
    /**
     * Safely delete a directory only if it's empty.
     * Logs a warning if the directory is not empty or deletion fails.
     *
     * @param string $disk Disk name (e.g., 'local', 's3'). Required.
     * @param string $path
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function deleteEmpty($disk, $path)
    {
        if (empty($disk)) {
            throw new \InvalidArgumentException('Disk parameter is required. Use "local" or "s3" or other configured disk name.');
        }

        $diskInstance = Storage::disk($disk);

        if (!$diskInstance->exists($path)) {
            return true;
        }

        if (!empty($diskInstance->allFiles($path))) {
            Log::warning("Cannot delete directory: {$path} - directory is not empty");
            return false;
        }

        try {
            $diskInstance->deleteDirectory($path);
            return true;
        } catch (\Exception $e) {
            Log::warning("Failed to delete directory: {$path} - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recursively delete all files and subdirectories, then delete the directory.
     * Logs a warning if the deletion fails.
     *
     * @param string $disk Disk name (e.g., 'local', 's3'). Required.
     * @param string $path
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function deleteRecursive($disk, $path)
    {
        if (empty($disk)) {
            throw new \InvalidArgumentException('Disk parameter is required. Use "local" or "s3" or other configured disk name.');
        }

        $diskInstance = Storage::disk($disk);

        if (!$diskInstance->exists($path)) {
            return true;
        }

        try {
            // Delete all files in the directory
            $files = $diskInstance->allFiles($path);
            if (!empty($files)) {
                $diskInstance->delete($files);
            }

            // Delete all subdirectories recursively
            $directories = $diskInstance->directories($path);
            foreach ($directories as $directory) {
                self::deleteRecursive($disk, $directory);
            }

            // Delete the directory itself
            $diskInstance->deleteDirectory($path);
            return true;
        } catch (\Exception $e) {
            Log::warning("Failed to recursively delete directory: {$path} - " . $e->getMessage());
            return false;
        }
    }
}
