<?php

namespace App\Util\Lexer;

class PrettyNumber {

  public static function convert($expression)
  {
    $abbrevs = array(12 => "T", 9 => "B", 6 => "M", 3 => "K", 0 => "");
      foreach($abbrevs as $exponent => $abbrev) {
          if($expression >= pow(10, $exponent)) {
            $display_num = $expression / pow(10, $exponent);
            $num = number_format($display_num,0) . $abbrev;
            return $num;
          }
      }
      return $expression;
  }

}