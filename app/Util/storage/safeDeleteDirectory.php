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
     * @param string $path
     * @param string $disk Disk name (e.g., 'local', 's3'). Required.
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function deleteEmpty($path, $disk)
    {
        if (empty($disk)) {
            throw new \InvalidArgumentException('Disk parameter is required. Use "local" or "s3" or other configured disk name.');
        }

        $disk = Storage::disk($disk);

        if (!$disk->exists($path)) {
            return true;
        }

        if (!empty($disk->allFiles($path))) {
            Log::warning("Cannot delete directory: {$path} - directory is not empty");
            return false;
        }

        try {
            $disk->deleteDirectory($path);
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
     * @param string $path
     * @param string $disk Disk name (e.g., 'local', 's3'). Required.
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function deleteRecursive($path, $disk)
    {
        if (empty($disk)) {
            throw new \InvalidArgumentException('Disk parameter is required. Use "local" or "s3" or other configured disk name.');
        }

        $disk = Storage::disk($disk);

        if (!$disk->exists($path)) {
            return true;
        }

        try {
            // Delete all files in the directory
            $files = $disk->allFiles($path);
            if (!empty($files)) {
                $disk->delete($files);
            }

            // Delete all subdirectories recursively
            $directories = $disk->directories($path);
            foreach ($directories as $directory) {
                self::deleteRecursive($directory, $disk);
            }

            // Delete the directory itself
            $disk->deleteDirectory($path);
            return true;
        } catch (\Exception $e) {
            Log::warning("Failed to recursively delete directory: {$path} - " . $e->getMessage());
            return false;
        }
    }
}
