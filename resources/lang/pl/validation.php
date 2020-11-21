<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => ':attribute musi być akceptowany.',
    'active_url'           => ':attribute nie jest prawidłowym adresem URL.',
    'after'                => ':attribute musi być datą późniejszą od :date.',
    'after_or_equal'       => ':attribute musi być datą późniejszą lub równą :date.',
    'alpha'                => ':attribute może zawierać tylko litery.',
    'alpha_dash'           => ':attribute może zawierać tylko znaki alfanumeryczne i podkreślniki.',
    'alpha_num'            => ':attribute może zawierać tylko znaki alfanumeryczne.',
    'array'                => ':attribute musi być tablicą.',
    'before'               => ':attribute musi być datą wcześniejszą od :date.',
    'before_or_equal'      => ':attribute musi być datą wcześniejszą lub równą :date.',
    'between'              => [
        'numeric' => ':attribute musi być liczbą pomiędzy :min a :max.',
        'file'    => ':attribute musi ważyć pomiędzy :min a :max kilobajtów.',
        'string'  => ':attribute musi zawierać od :min do :max znaków.',
        'array'   => ':attribute musi zawierać od :min do :max elementów.',
    ],
    'boolean'              => ':attribute musi być wartością `true` lub `false`.',
    'confirmed'            => 'Potwierdzenie :attribute nie jest zgodne.',
    'date'                 => ':attribute nie jest prawidłową datą.',
    'date_format'          => ':attribute nie pasuje do formatu :format.',
    'different'            => ':attribute i :other nie mogą być takie same.',
    'digits'               => ':attribute musi zawierać :digits cyfr.',
    'digits_between'       => ':attribute musi zawierać pomiędzy :min a :max cyfr.',
    'dimensions'           => ':attribute ma nieprawidłowe wymiary  obrazu.',
    'distinct'             => 'Pole :attribute zawiera zduplikowaną wartość.',
    'email'                => ':attribute musi być prawidłowym adresem e-mail.',
    'exists'               => 'Zaznaczony :attribute jest nieprawidłowy.',
    'file'                 => ':attribute musi być pliki.',
    'filled'               => 'Pole :attribute nie może być puste.',
    'image'                => ':attribute musi być obrazem.',
    'in'                   => 'Zaznaczony :attribute jest nieprawidłowy.',
    'in_array'             => 'Pole :attribute nie występuje w :other.',
    'integer'              => ':attribute musi być liczbą całkowitą.',
    'ip'                   => ':attribute musi być prawidłowym adresem IP.',
    'ipv4'                 => ':attribute musi być prawidłowym adresem IPv4.',
    'ipv6'                 => ':attribute musi być prawidłowym adresem IPv6.',
    'json'                 => ':attribute musi być prawidłowym ciągiem znaków JSON.',
    'max'                  => [
        'numeric' => ':attribute nie może być większy niż :max.',
        'file'    => ':attribute nie może być większy niż :max kilobajtów.',
        'string'  => ':attribute nie może zawierać więcej niż :max znaków.',
        'array'   => ':attribute nie może składać się z więcej niż :max elementów.',
    ],
    'mimes'                => ':attribute musi być plikiem typu :values.',
    'mimetypes'            => ':attribute musi być plikiem typu :values.',
    'min'                  => [
        'numeric' => ':attribute musi wynosić przynajmniej :min.',
        'file'    => ':attribute musi ważyć przynajmniej :min kilobajtów.',
        'string'  => ':attribute musi składać się z przynajmniej :min znaków.',
        'array'   => ':attribute musi zawierać przynajmniej :min elementów.',
    ],
    'not_in'               => 'Wybrany :attribute jest nieprawidłowy.',
    'not_regex'            => 'Format :attribute jest nieprawidłowy.',
    'numeric'              => ':attribute musi być liczbą.',
    'present'              => 'Pole :attribute musi być obecne.',
    'regex'                => 'Format :attribute jest nieprawidłowy.',
    'required'             => 'Pole :attribute jest wymagane.',
    'required_if'          => 'Pole :attribute musi być wypełnione, jeżeli wartość :other to :value.',
    'required_unless'      => 'Pole :attribute musi być wypełnione, jeżeli wartość :other nie jest jedną z :values.',
    'required_with'        => 'Pole :attribute musi być wypełnione, jeżeli :values jest obecne.',
    'required_with_all'    => 'Pole :attribute musi być wypełnione, jeżeli :values są obecne.',
    'required_without'     => 'Pole :attribute hest wymagane, jeżeli :values nie są obecne.',
    'required_without_all' => 'Pole :attribute jest wymagane, jeżeli żadne z :values nie są obecne.',
    'same'                 => ':attribute i :other muszą się zgadzać.',
    'size'                 => [
        'numeric' => ':attribute musi mieć rozmiar :size.',
        'file'    => ':attribute musi mieć rozmiar :size kilobajtów.',
        'string'  => ':attribute musi zawierać :size znaków.',
        'array'   => ':attribute musi zawierać :size elementów.',
    ],
    'string'               => ':attribute musi być ciągiem znaków.',
    'timezone'             => ':attribute musi być prawidłową strefą.',
    'unique'               => ':attribute został już użyty.',
    'uploaded'             => 'Nie udało się wysłać :attribute.',
    'url'                  => 'Format :attribute jest nieprawidłowy.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

];
