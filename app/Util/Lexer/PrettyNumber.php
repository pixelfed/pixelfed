<?php

namespace App\Util\Lexer;

class PrettyNumber
{
    public static function convert($expression)
    {
        $abbrevs = [12 => 'T', 9 => 'B', 6 => 'M', 3 => 'K', 0 => ''];
        foreach ($abbrevs as $exponent => $abbrev) {
            if ($expression >= pow(10, $exponent)) {
                $display_num = $expression / pow(10, $exponent);
                $num = number_format($display_num, 0).$abbrev;

                return $num;
            }
        }

        return $expression;
    }

    public static function size($expression, $kb = false)
    {
        if ($kb) {
            $expression = $expression * 1024;
        }
        $size = intval($expression);
        $precision = 0;
        $short = true;
        $units = $short ?
          ['B', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'] :
          ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {
        $res = round($size, $precision).$units[$i];
        }
        return $res;
    }
}
