<?php

namespace App\Services;

class HashidService
{
    public const CMAP = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

    public static function encode($id, $minLimit = true)
    {
        if (! is_numeric($id) || $id > PHP_INT_MAX) {
            return null;
        }

        $cmap = self::CMAP;
        $base = strlen($cmap);
        $shortcode = '';
        while ($id) {
            $id = ($id - ($r = $id % $base)) / $base;
            $shortcode = $cmap[$r].$shortcode;
        }

        return $shortcode;
    }

    public static function decode($short = false)
    {
        if (! $short) {
            return;
        }
        $id = 0;
        foreach (str_split($short) as $needle) {
            $pos = strpos(self::CMAP, $needle);
            $id = ($id * 64) + $pos;
        }

        return $id;
    }
}
