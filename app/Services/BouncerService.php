<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\IpUtils;

class BouncerService
{
    public static function checkIp($ip)
    {
        $knownCloudCidrs = Cache::rememberForever('pf:bouncer-service:check-ip:known-cloud-cidrs', function () {
            $file = Storage::get('bouncer/all.json');

            if ($file === null) {
                return [];
            }

            $decoded = json_decode($file, true);

            if ($decoded === null || ! is_array($decoded)) {
                return [];
            }

            return $decoded;
        });

        return IpUtils::checkIp($ip, $knownCloudCidrs);
    }
}
