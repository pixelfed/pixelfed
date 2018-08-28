<?php

/**
 * @author     Takashi Nojima
 * @copyright  Copyright 2014, Takashi Nojima
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 */

namespace App\Util\Lexer;

/**
 * String utility.
 *
 * @author     Takashi Nojima
 * @copyright  Copyright 2014, Takashi Nojima
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 */
class StringUtils
{
    /**
     * alias of mb_substr.
     *
     * @param string $str
     * @param int    $start
     * @param int    $length
     * @param string $encoding
     *
     * @return string
     */
    public static function substr($str, $start, $length = null, $encoding = 'UTF-8')
    {
        if (is_null($length)) {
            // for PHP <= 5.4.7
            $length = mb_strlen($str, $encoding);
        }

        return mb_substr($str, $start, $length, $encoding);
    }

    /**
     * alias of mb_strlen.
     *
     * @param string $str
     * @param string $encoding
     *
     * @return int
     */
    public static function strlen($str, $encoding = 'UTF-8')
    {
        return mb_strlen($str, $encoding);
    }

    /**
     * alias of mb_strpos.
     *
     * @param string $haystack
     * @param string $needle
     * @param int    $offset
     * @param string $encoding
     *
     * @return int
     */
    public static function strpos($haystack, $needle, $offset = 0, $encoding = 'UTF-8')
    {
        return mb_strpos($haystack, $needle, $offset, $encoding);
    }

    /**
     * A multibyte-aware substring replacement function.
     *
     * @param string $string      The string to modify.
     * @param string $replacement The replacement string.
     * @param int    $start       The start of the replacement.
     * @param int    $length      The number of characters to replace.
     * @param string $encoding    The encoding of the string.
     *
     * @return string The modified string.
     *
     * @see http://www.php.net/manual/en/function.substr-replace.php#90146
     */
    public static function substrReplace($string, $replacement, $start, $length = null, $encoding = 'UTF-8')
    {
        if (extension_loaded('mbstring') === true) {
            $string_length = static::strlen($string, $encoding);
            if ($start < 0) {
                $start = max(0, $string_length + $start);
            } elseif ($start > $string_length) {
                $start = $string_length;
            }
            if ($length < 0) {
                $length = max(0, $string_length - $start + $length);
            } elseif ((is_null($length) === true) || ($length > $string_length)) {
                $length = $string_length;
            }
            if (($start + $length) > $string_length) {
                $length = $string_length - $start;
            }

            $suffixOffset = $start + $length;
            $suffixLength = $string_length - $start - $length;

            return static::substr($string, 0, $start, $encoding).$replacement.static::substr($string, $suffixOffset, $suffixLength, $encoding);
        }

        return (is_null($length) === true) ? substr_replace($string, $replacement, $start) : substr_replace($string, $replacement, $start, $length);
    }
}
