<?php

namespace App\Util\Lexer;

use Illuminate\Support\Str;

class Nickname
{
    public static function normalizeProfileUrl($url)
    {
        if(!Str::of($url)->contains('@')) {
            return;
        }

        if(Str::startsWith($url, 'acct:')) {
            $url = str_replace('acct:', '', $url);
        }

        if(Str::startsWith($url, '@')) {
            $url = substr($url, 1);

            if(!Str::of($url)->contains('@')) {
                return;
            }
        }

        $parts = explode('@', $url);
        $username = $parts[0];
        $domain = $parts[1];

        return [
            'domain' => $domain, 
            'username' => $username
        ];
    }
}
