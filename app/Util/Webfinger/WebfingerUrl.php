<?php

namespace App\Util\Webfinger;

use App\Util\Lexer\Nickname;

class WebfingerUrl
{
    public static function generateWebfingerUrl($url)
    {
        $handle = Nickname::normalizeProfileUrl($url);
        if (is_array($handle)) {
            // the url was a user handle
            $domain = $handle['domain'];
            $username = $handle['username'];
            $resource = "acct:{$username}@{$domain}";
        } else {
            // it could be an actual URL https://domain/endpoint
            $resource = $url;
            if (str_starts_with($resource, 'http')) {
                $m = parse_url($resource);
                if ($m) {
                    if ($m['scheme'] !== 'https') {
                        return false;
                    }
                    if (!array_key_exists('host', $m)) {
                        return false;
                    }
                    $domain = $m['host'] . (array_key_exists('port', $m) ? ':' . $m['port'] : '');
                } else {
                    return false;
                }
            }

        }

        $path = "https://{$domain}/.well-known/webfinger?resource={$resource}";
        return $path;
    }
}
