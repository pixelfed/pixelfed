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
    'after'                => ':attribute skal være en dato efter :date.',
    'after_or_equal'       => ':attribute skal være en dato ens med, eller efter :date.',
    'alpha'                => ':attribute må kun indeholde bogstaver.',
    'alpha_dash'           => ':attribute må kun indeholde bogstaver, tal og bindestreger.',
    'alpha_num'            => ':attribute må kun indeholde bogstaver og tal.',
    'array'                => ':attribute skal være et Array.',
    'before'               => ':attribute skal være en dato før :date.',
    'before_or_equal'      => ':attribute skal være en dato før, eller det samme som :date.',
    'between'              => [
        'numeric' => ':attribute skal være mellem :min og :max.',
        'file'    => ':attribute skal være mellem :min og :max kilobytes.',
        'string'  => ':attribute skal være mellem :min og :max tegn.',
        'array'   => ':attribute skal være mellem :min og :max elementer.',
    ],
    'boolean'              => ':attribute felt skal være sandt eller falsk.',
    'confirmed'            => ':attribute bekræftelse matcher ikke.',
    'date'                 => ':attribute er ikke en gyldig dato.',
    'date_format'          => ':attribute matcher ikke formatet :format.',
    'different'            => ':attribute og :other skal være forskellige.',
    'digits'               => ':attribute skal være :digits tal.',
    'digits_between'       => ':attribute skal være mellem :min og :max tal.',
    'dimensions'           => ':attribute har ugyldige billeddimensioner.',
    'distinct'             => ':attribute-felt har en dobbeltværdi.',
    'email'                => ':attribute skal være en gyldig emailadresse.',
    'exists'               => 'Den valgte :attribute er ugyldig.',
    'file'                 => ':attribute skal være en fil.',
    'filled'               => 'Feltet :attribute skal have en værdi.',
    'image'                => ':attribute skal være et billede.',
    'in'                   => 'Den valgte :attribute er ugyldig.',
    'in_array'             => ':attribute feltet findes ikke i :other.',
    'integer'              => ':attribute skal være et heltal.',
    'ip'                   => ':attribute skal være en gyldig IP-adresse.',
    'ipv4'                 => ':attribute skal være en gyldig IPv4 adresse.',
    'ipv6'                 => ':attribute skal være en gyldig IPv6 adresse.',
    'json'                 => ':attribute skal være en gyldig JSON-streng.',
    'max'                  => [
        'numeric' => ':attribute må ikke være større end :max.',
        'file'    => ':attribute må ikke være større end :max kilobytes.',
        'string'  => ':attribute må ikke være større end :max tegn.',
        'array'   => ':attribute må ikke have mere end :max elementer.',
    ],
    'mimes'                => ':attribute skal være en fil af typen: :values.',
    'mimetypes'            => ':attribute skal være en fil af typen: :values.',
    'min'                  => [
        'numeric' => ':attribute skal være mindst :min.',
        'file'    => ':attribute skal være mindst :min kilobytes.',
        'string'  => ':attribute skal være mindst :min tegn.',
        'array'   => ':attribute skal være mindst :min elementer.',
    ],
    'not_in'               => 'Den valgte :attribute er ugyldig.',
    'not_regex'            => ':attribute format er ugyldigt.',
    'numeric'              => ':attribute skal være et tal.',
    'present'              => ':attribute feltet skal være til stede.',
    'regex'                => ':attribute format er ugyldigt.',
    'required'             => ':attribute felt er påkrævet.',
    'required_if'          => ':attribute felt er påkrævet når :other er :value.',
    'required_unless'      => ':attribute felt er påkrævet medmindre :other er i :values.',
    'required_with'        => ':attribute felt er påkrævet når :values er tilstede.',
    'required_with_all'    => ':attribute felt er påkrævet når :values er tilstede.',
    'required_without'     => ':attribute felt er påkrævet når :values ikke er tilstede.',
    'required_without_all' => ':attribute felt er påkrævet når ingen af :values er tilstede.',
    'same'                 => ':attribute og :other skal være ens.',
    'size'                 => [
        'numeric' => ':attribute skal være :size.',
        'file'    => ':attribute skal være :size kilobytes.',
        'string'  => ':attribute skal være :size tegn.',
        'array'   => ':attribute skal indeholde :size elementer.',
    ],
    'string'               => ':attribute skal være en streng.',
    'timezone'             => ':attribute skal være en gyldig zone.',
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
