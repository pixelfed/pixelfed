<?php

namespace App\Util\Lexer;

class Nickname
{
    public static function normalizeProfileUrl($url)
    {
        if (starts_with($url, 'acct:')) {
            $url = str_replace('acct:', '', $url);
        }

        if(starts_with($url, '@')) {
            $url = substr($url, 1);
        }

        $parts = explode('@', $url);
        $username = $parts[0];
        $domain = $parts[1];

        if(parse_url(filter_var('https://test.dev', FILTER_VALIDATE_URL), PHP_URL_HOST))

        return ['domain' => $domain, 'username' => $username];
    }
}
