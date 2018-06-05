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

    'accepted'             => ':attribute skal accepteres.',
    'active_url'           => ':attribute er ikke en gyldig URL.',
    'after'                => ':attribute skal v&aelig;re en dato efter :date.',
    'after_or_equal'       => ':attribute skal v&aelig;re en dato ens med, eller efter :date.',
    'alpha'                => ':attribute m&aring; kun indeholde bogstaver.',
    'alpha_dash'           => ':attribute m&aring; kun indeholde bogstaver, tal og bindestreger.',
    'alpha_num'            => ':attribute m&aring; kun indeholde bogstaver og tal.',
    'array'                => ':attribute skal v&aelig;re et Array.',
    'before'               => ':attribute skal v&aelig;re en dato f&oslash;r :date.',
    'before_or_equal'      => ':attribute skal v&aelig;re en dato f&oslash;r, eller det samme som :date.',
    'between'              => [
        'numeric' => ':attribute skal v&aelig;re mellem :min og :max.',
        'file'    => ':attribute skal v&aelig;re mellem :min og :max kilobytes.',
        'string'  => ':attribute skal v&aelig;re mellem :min og :max tegn.',
        'array'   => ':attribute skal v&aelig;re mellem :min og :max elementer.',
    ],
    'boolean'              => ':attribute felt skal v&aelig;re sandt eller falsk.',
    'confirmed'            => ':attribute bekr&aelig;ftelse matcher ikke.',
    'date'                 => ':attribute er ikke en gyldig dato.',
    'date_format'          => ':attribute matcher ikke formatet :format.',
    'different'            => ':attribute og :other skal v&aelig;re forskellige.',
    'digits'               => ':attribute skal v&aelig;re :digits tal.',
    'digits_between'       => ':attribute skal v&aelig;re mellem :min og :max tal.',
    'dimensions'           => ':attribute har ugyldige billeddimensioner.',
    'distinct'             => ':attribute-felt har en dobbeltv&aelig;rdi.',
    'email'                => ':attribute skal v&aelig;re en gyldig emailadresse.',
    'exists'               => 'Den valgte :attribute er ugyldig.',
    'file'                 => ':attribute skal v&aelig;re en fil.',
    'filled'               => 'Feltet :attribute skal have en v&aelig;rdi.',
    'image'                => ':attribute skal v&aelig;re et billede.',
    'in'                   => 'Den valgte :attribute er ugyldig.',
    'in_array'             => ':attribute feltet findes ikke i :other.',
    'integer'              => ':attribute skal v&aelig;re et heltal.',
    'ip'                   => ':attribute skal v&aelig;re en gyldig IP-adresse.',
    'ipv4'                 => ':attribute skal v&aelig;re en gyldig IPv4 adresse.',
    'ipv6'                 => ':attribute skal v&aelig;re en gyldig IPv6 adresse.',
    'json'                 => ':attribute skal v&aelig;re en gyldig JSON-streng.',
    'max'                  => [
        'numeric' => ':attribute m&aring; ikke v&aelig;re st&oslash;rre end :max.',
        'file'    => ':attribute m&aring; ikke v&aelig;re st&oslash;rre end :max kilobytes.',
        'string'  => ':attribute m&aring; ikke v&aelig;re st&oslash;rre end :max tegn.',
        'array'   => ':attribute m&aring; ikke have mere end :max elementer.',
    ],
    'mimes'                => ':attribute skal v&aelig;re en fil af typen: :values.',
    'mimetypes'            => ':attribute skal v&aelig;re en fil af typen: :values.',
    'min'                  => [
        'numeric' => ':attribute skal v&aelig;re mindst :min.',
        'file'    => ':attribute skal v&aelig;re mindst :min kilobytes.',
        'string'  => ':attribute skal v&aelig;re mindst :min tegn.',
        'array'   => ':attribute skal v&aelig;re mindst :min elementer.',
    ],
    'not_in'               => 'Den valgte :attribute er ugyldig.',
    'not_regex'            => ':attribute format er ugyldigt.',
    'numeric'              => ':attribute skal v&aelig;re et tal.',
    'present'              => ':attribute feltet skal v&aelig;re til stede.',
    'regex'                => ':attribute format er ugyldigt.',
    'required'             => ':attribute felt er p&aring;kr&aelig;vet.',
    'required_if'          => ':attribute felt er p&aring;kr&aelig;vet n&aring;r :other er :value.',
    'required_unless'      => ':attribute felt er p&aring;kr&aelig;vet medmindre :other er i :values.',
    'required_with'        => ':attribute felt er p&aring;kr&aelig;vet n&aring;r :values er tilstede.',
    'required_with_all'    => ':attribute felt er p&aring;kr&aelig;vet n&aring;r :values er tilstede.',
    'required_without'     => ':attribute felt er p&aring;kr&aelig;vet n&aring;r :values ikke er tilstede.',
    'required_without_all' => ':attribute felt er p&aring;kr&aelig;vet n&aring;r ingen af :values er tilstede.',
    'same'                 => ':attribute og :other skal v&aelig;re ens.',
    'size'                 => [
        'numeric' => ':attribute skal v&aelig;re :size.',
        'file'    => ':attribute skal v&aelig;re :size kilobytes.',
        'string'  => ':attribute skal v&aelig;re :size tegn.',
        'array'   => ':attribute skal indeholde :size elementer.',
    ],
    'string'               => ':attribute skal v&aelig;re en streng.',
    'timezone'             => ':attribute skal v&aelig;re en gyldig zone.',
    'unique'               => ':attribute er allerede taget.',
    'uploaded'             => ':attribute kunne ikke uploades.',
    'url'                  => ':attribute format er ugyldigt.',

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
