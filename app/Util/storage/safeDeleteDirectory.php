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
     * @return bool
     */
    public static function safe($path)
    {
        if (!Storage::exists($path)) {
            return true;
        }

        if (!empty(Storage::allFiles($path))) {
            Log::warning("Cannot delete directory: {$path} - directory is not empty");
            return false;
        }

        try {
            Storage::deleteDirectory($path);
            return true;
        } catch (\Exception $e) {
            Log::warning("Failed to delete directory: {$path} - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recursively delete all files and subdirectories, then delete the directory.
     *
     * @param string $path
     * @return bool
     */
    public static function recursive($path)
    {
        if (!Storage::exists($path)) {
            return true;
        }

        try {
            // Delete all files in the directory
            $files = Storage::allFiles($path);
            if (!empty($files)) {
                Storage::delete($files);
            }

            // Delete all subdirectories recursively
            $directories = Storage::directories($path);
            foreach ($directories as $directory) {
                self::recursive($directory);
            }

            // Delete the directory itself
            Storage::deleteDirectory($path);
            return true;
        } catch (\Exception $e) {
            Log::warning("Failed to recursively delete directory: {$path} - " . $e->getMessage());
            return false;
        }
    }
}
