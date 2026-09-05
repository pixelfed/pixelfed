<?php

namespace App\Util\Localization;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Localization
{
    public static function languages()
    {
        return Cache::remember('core:localization:languages', now()->addDays(1), function () {
            $dir = lang_path();

            return Arr::flatten(array_diff(scandir($dir), ['..', '.', 'vendor', '.DS_Store']));
        });
    }
}
