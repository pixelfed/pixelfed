<?php

namespace App\Rules;

use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\InvokableRule;

class MaxMultiLine implements InvokableRule
{
    private $maxCharacters;

    public function __construct($maxCharacters)
    {
        $this->maxCharacters = $maxCharacters;
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        $realCount = Str::length($value) - Str::substrCount($value, "\r\n");

        if($realCount > $this->maxCharacters)
        {
            $fail('validation.max.string')->translate(['max' => $this->maxCharacters]);
        }
    }
}
