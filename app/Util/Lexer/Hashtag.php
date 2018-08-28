<?php

namespace App\Util\Lexer;

class Hashtag
{
    public static function getHashtags($status)
    {
        $hashtags = false;
        preg_match_all("/(?<!&)(#\w+)/u", $status, $matches);
        if ($matches) {
            $res = array_count_values($matches[0]);
            $hashtags = array_keys($res);
        }

        return $hashtags;
    }

    public static function replaceHashtagsWithLinks($status)
    {
        $hashtags = self::getHashtags($status);
        if (!$hashtags) {
            return false;
        }

        $rendered = $status;

        foreach ($hashtags as $hashtag) {
            $tag = substr($hashtag, 1);
            $link = config('routes.hashtag.search').$tag;
            $href = "<a href='{$link}' class='mention hashtag status-link' rel='noopener'>{$hashtag}</a>";
            $rendered = str_replace($hashtag, $href, $rendered);
        }

        return $rendered;
    }
}
