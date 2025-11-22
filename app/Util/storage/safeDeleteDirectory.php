<?php

namespace App\Util\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SafeDeleteDirectory
{
    /**
     * Recursively delete all files and subdirectories, then delete the directory.
     * Logs a warning if the deletion fails.
     *
     * @param string $diskName Disk name (e.g. 'local', 's3'). Required.
     * @param string $path
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public static function deleteRecursive(string $diskName, string $path): bool
    {
        if ($diskName === '') {
            throw new \InvalidArgumentException(
                'SafeDeleteDirectory: diskName parameter required. Use "local" or "s3".'
            );
        }

        $disk = Storage::disk($diskName);

        if (!$disk->exists($path)) {
            return true;
        }

        try {
            // Delete all files in the directory (recursively)
            $files = $disk->allFiles($path);
            if (!empty($files)) {
                $deletedFiles = $disk->delete($files);

                // If the driver reports a failure, log and stop here
                if ($deletedFiles === false) {
                    Log::warning("SafeDeleteDirectory: Failed to delete one or more files in directory {$path} on disk {$diskName}.");
                    return false;
                }
            }

            // Delete all subdirectories recursively
            $directories = $disk->directories($path);
            foreach ($directories as $directory) {
                if (!self::deleteRecursive($diskName, $directory)) {
                    return false;
                }
            }

            // Delete the directory itself
            $deletedDir = $disk->deleteDirectory($path);

            if (!$deletedDir) {
                Log::warning("SafeDeleteDirectory: Failed to delete directory {$path} on disk {$diskName}.");
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::warning(
                "SafeDeleteDirectory: Failed to recursively delete directory {$path} on disk {$diskName}: " . $e->getMessage()
            );

            return false;
        }
    }
}
