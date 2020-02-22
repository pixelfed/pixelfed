<?php

namespace App\Util\Localization;

use Cache;
use Illuminate\Support\Arr;

class Localization
{

    public static function languages()
    {
        return Cache::remember('core:localization:languages', now()->addDays(1), function () {
            $dir = resource_path('lang');
            return Arr::flatten(array_diff(scandir($dir), array('..', '.', 'vendor')));
        });
    }
}
