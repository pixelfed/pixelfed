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

    'accepted'             => 'O :attribute debe aceptarse.',
    'active_url'           => 'O :attribute non é un URL válido.',
    'after'                => 'A :attribute debe ser unha data posterior :date.',
    'after_or_equal'       => 'A :attribute debe ser unha data posterior ou igual a :date.',
    'alpha'                => 'O :attribute só pode conter letras.',
    'alpha_dash'           => 'O :attribute pode conter só letras, números e trazos.',
    'alpha_num'            => 'O :attribute pode conter só letras e números.',
    'array'                => 'A :attribute debe ser unha cadea.',
    'before'               => 'A :attribute debe ser unha data anterior a :date.',
    'before_or_equal'      => 'A :attribute debe ser unha data anterior ou igual a  :date.',
    'between'              => [
        'numeric' => ' :attribute debe estar entre :min e :max.',
        'file'    => 'O :attribute debe estar entre :min e :max kilobytes.',
        'string'  => 'O :attribute debe ter entre :min e :max caracteres.',
        'array'   => 'O :attribute debe ter entre :min e :max elementos.',
    ],
    'boolean'              => 'O campo :attribute debe ser verdadeiro ou falso.',
    'confirmed'            => 'O :attribute de confirmación non concorda.',
    'date'                 => 'A :attribute non é unha data válida.',
    'date_format'          => 'O :attribute non segue o formato :format.',
    'different'            => ' :attribute e :other deben ser diferentes.',
    'digits'               => ' :attribute deben ser :digits díxitos.',
    'digits_between'       => ' :attribute debe ter entre :min e :max díxitos.',
    'dimensions'           => ' :attribute ten unhas dimensións de imaxe non válidas.',
    'distinct'             => 'O campo :attribute ten un valor duplo.',
    'email'                => ' :attribute debe ser un enderezo de correo válido.',
    'exists'               => 'O :attribute escollido non é válido.',
    'file'                 => ' :attribute debe ser un ficheiro.',
    'filled'               => 'O campo :attribute debe ter un valor.',
    'image'                => ' :attribute ten que ser unha imaxe.',
    'in'                   => ' :attribute escollido non é válido.',
    'in_array'             => 'O campo :attribute non existe en :other.',
    'integer'              => ' :attribute debe ser un enteiro.',
    'ip'                   => ' :attribute ten que ser un enderezo IP válido.',
    'ipv4'                 => ' :attribute ten que ser un enderezo IPv4 válido.',
    'ipv6'                 => ' :attribute ten que ser un enderezo IPv6 válido.',
    'json'                 => ' :attribute debe ser unha cadea JSON válida.',
    'max'                  => [
        'numeric' => ' :attribute non pode ser maior de :max.',
        'file'    => ' :attribute non pode ser maior de :max kilobytes.',
        'string'  => ' :attribute non pode ser maior de :max caracteres.',
        'array'   => 'O :attribute non pode ter máis de :max elementos.',
    ],
    'mimes'                => ' :attribute debe ser un ficheiro de tipo: :values.',
    'mimetypes'            => ' :attribute debe ser un ficheiro de tipo: :values.',
    'min'                  => [
        'numeric' => ' :attribute debe ser como mínimo :min.',
        'file'    => ' :attribute debe ser como mínimo :min kilobytes.',
        'string'  => ' :attribute debe ser como mínimo :min characters.',
        'array'   => ' :attribute debe ter ao menos :min elementos.',
    ],
    'not_in'               => 'O :attribute escollido non é válido.',
    'not_regex'            => 'O formato de :attribute non é válido',
    'numeric'              => ' :attribute debe ser un número.',
    'present'              => 'O campo :attribute debe estar presente.',
    'regex'                => 'O formato de :attribute non é válido.',
    'required'             => 'O campo :attribute é requerido.',
    'required_if'          => 'O campo :attribute é requerido cando :other é :value.',
    'required_unless'      => 'O campo :attribute é requerido a non ser que :other esté en :values.',
    'required_with'        => 'O campo :attribute é requerido cando :values está presente.',
    'required_with_all'    => 'O campo :attribute é requerido cando :values está presente.',
    'required_without'     => 'O campo :attribute é requerido cando :values non está presente.',
    'required_without_all' => 'O campo :attribute é requerido cando non está presente :values.',
    'same'                 => ' :attribute e :other deben coincidir.',
    'size'                 => [
        'numeric' => ' :attribute debe ser :size.',
        'file'    => ' :attribute debe ser :size kilobytes.',
        'string'  => ' :attribute debe ser :size caracteres.',
        'array'   => ' :attribute debe conter :size elementos.',
    ],
    'string'               => ' :attribute debe ser unha cadea.',
    'timezone'             => ' :attribute debe ser unha zona válida.',
    'unique'               => 'O nome :attribute xa está collido.',
    'uploaded'             => ' :attribute fallou ao subir.',
    'url'                  => 'O formato de :attribute non é válido.',

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
