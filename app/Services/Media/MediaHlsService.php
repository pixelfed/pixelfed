<?php

namespace App\Services\Media;

use Storage;

class MediaHlsService
{
    public static function allFiles($media)
    {
        $path = $media->media_path;
        if(!$path) { return; }
        $parts = explode('/', $path);
        $filename = array_pop($parts);
        $dir = implode('/', $parts);
        [$name, $ext] = explode('.', $filename);

        $files = Storage::files($dir);

        return collect($files)
            ->filter(function($p) use($dir, $name) {
                return str_starts_with($p, $dir . '/' . $name);
            })
            ->values()
            ->toArray();
    }
}
