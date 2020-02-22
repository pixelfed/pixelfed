<?php

namespace App\Util\Lexer;

class Nickname
{
    public static function normalizeProfileUrl($url)
    {
        if (starts_with($url, 'acct:')) {
            $url = str_replace('acct:', '', $url);
        }

        if (!str_contains($url, '@') && filter_var($url, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($url);
            $username = str_replace(['/', '\\', '@'], '', $parsed['path']);

            return ['domain' => $parsed['host'], 'username' => $username];
        }
        $parts = explode('@', $url);
        $username = null;
        $domain = null;

        foreach ($parts as $part) {
        // skip empty array slices
            if (empty($part)) {
                continue;
            }

            // if slice contains . assume its a domain
            if (str_contains($part, '.')) {
                $domain = filter_var($part, FILTER_VALIDATE_URL) ?
                    parse_url($part, PHP_URL_HOST) :
                    $part;
            } else {
                $username = $part;
            }
        }

        return ['domain' => $domain, 'username' => $username];
    }
}
