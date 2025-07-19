<?php

namespace App\Util\Webfinger;

use App\Services\InstanceService;
use App\Util\Lexer\Nickname;

class WebfingerUrl
{
    public static function get($url)
    {
        $n = Nickname::normalizeProfileUrl($url);
        if (! $n || ! isset($n['domain'], $n['username'])) {
            return false;
        }
        if (in_array($n['domain'], InstanceService::getBannedDomains())) {
            return false;
        }

        return 'https://'.$n['domain'].'/.well-known/webfinger?resource=acct:'.$n['username'].'@'.$n['domain'];
    }

    public static function generateWebfingerUrl($url)
    {
        $url = Nickname::normalizeProfileUrl($url);
        $domain = $url['domain'];
        $username = $url['username'];
        $path = "https://{$domain}/.well-known/webfinger?resource=acct:{$username}@{$domain}";

        return $path;
    }
}
