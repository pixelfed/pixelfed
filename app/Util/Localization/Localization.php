<?php

namespace App\Util\Localization;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Localization
{
    public static function languages()
    {
        return Cache::remember('core:localization:languages', now()->addDays(1), function () {
            $dir = resource_path('lang');

            return Arr::flatten(array_diff(scandir($dir), ['..', '.', 'vendor', '.DS_Store']));
        });
    }
}
