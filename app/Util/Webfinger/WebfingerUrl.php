<?php

namespace App\Util\Webfinger;

use App\Util\Lexer\Nickname;

class WebfingerUrl
{
    public static function generateWebfingerUrl($url): string
    {
        $url = Nickname::normalizeProfileUrl($url);
        $domain = $url['domain'];
        $username = $url['username'];
        $path = "https://{$domain}/.well-known/webfinger?resource={$username}@{$domain}";

        return $path;
    }
}
