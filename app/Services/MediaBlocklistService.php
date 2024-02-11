<?php

namespace App\Services;

use App\MediaBlocklist;

class MediaBlocklistService
{
    public static function get()
    {
        return MediaBlocklist::whereActive(true)
            ->pluck('sha256')
            ->toArray();
    }

    public static function exists($hash)
    {
        $hashes = self::get();

        return in_array($hash, $hashes) == true;
    }

    public static function remove($hash)
    {
        if (! self::exists($hash)) {
            return;
        }
        MediaBlocklist::whereSha256($hash)->delete();

    }

    public static function add($hash, $metadata)
    {
        $m = new MediaBlocklist;
        $m->sha256 = $hash;
        $m->active = true;
        $m->metadata = json_encode($metadata);
        $m->save();

        return $m;
    }
}
