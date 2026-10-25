<?php

namespace App\Util\Lexer;

class Markdown
{
    // Links and other tags pass through untouched, so markers inside them are ignored
    const TAG = '<a\b[^>]*>.*?<\/a>|<[^>]*>';

    // Whole elements a bold or italic span may wrap
    const ELEMENT = '<a\b[^>]*>.*?<\/a>|<strong>.*?<\/strong>';

    /**
     * Render **bold** and *italic* in caption HTML produced by Autolink.
     */
    public static function inline(string $html): string
    {
        $html = self::replace($html, '\*\*(?![\s*])((?:'.self::ELEMENT.'|[^<\n])+?)(?<![\s*])\*\*', 'strong');

        return self::replace($html, '(?<![*\w])\*(?![\s*])((?:'.self::ELEMENT.'|[^<*\n])+?)(?<![\s*])\*(?![*\w])', 'em');
    }

    protected static function replace(string $html, string $pattern, string $tag): string
    {
        return preg_replace_callback('/('.self::TAG.')|'.$pattern.'/u', function ($m) use ($tag) {
            return $m[1] !== '' ? $m[1] : "<{$tag}>{$m[2]}</{$tag}>";
        }, $html) ?? $html;
    }
}
