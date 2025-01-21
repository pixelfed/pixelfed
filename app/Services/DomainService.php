<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class DomainService
{
    const CACHE_KEY = 'pf:services:domains:';

    public static function hasValidDns($domain)
    {
        if (! $domain || ! strlen($domain) || strpos($domain, '.') == -1) {
            return false;
        }

        if (config('security.url.trusted_domains')) {
            if (in_array($domain, explode(',', config('security.url.trusted_domains')))) {
                return true;
            }
        }

        $valid = filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);

        if (! $valid) {
            return false;
        }

        return Cache::remember(self::CACHE_KEY.'valid-dns:'.$domain, 1800, function () use ($domain) {
            return count(dns_get_record($domain, DNS_A | DNS_AAAA)) > 0;
        });
    }
}
