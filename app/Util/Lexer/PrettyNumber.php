<?php

namespace App\Util\Lexer;

class PrettyNumber
{
	public static function convert($number, $showDecimals = true)
	{
		if(!is_integer($number)) {
			return $number;
		}

		$abbrevs = [12 => 'T', 9 => 'B', 6 => 'M', 3 => 'K', 0 => ''];
		foreach ($abbrevs as $exponent => $abbrev) {
			if(abs($number) >= pow(10, $exponent)) {
				$display = $number / pow(10, $exponent);
				$decimals = ($exponent >= 3 && round($display) < 100) ? 1 : 0;
				$decimals = !$showDecimals ? 0 : $decimals;
				$number = number_format($display, $decimals).$abbrev;
				break;
			}
		}

		return $number;
	}

	public static function size($expression, $kb = false, $short = true)
	{
		if ($kb) {
			$expression = $expression * 1024;
		}
		$size = intval($expression);
		$precision = 0;
		$units = $short ?
		  ['B', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'] :
		  ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
		for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {
		}
		$res = round($size, $precision).$units[$i];

		return $res;
	}
}
