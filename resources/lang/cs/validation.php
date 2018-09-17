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

    'accepted'             => ':attribute musí být akceptován.',
    'active_url'           => ':attribute není platná URL adresa.',
    'after'                => ':attribute musí být datum po :date.',
    'after_or_equal'       => ':attribute musí být datum po nebo rovný datu :date.',
    'alpha'                => ':attribute musí obsahovat pouze písmena.',
    'alpha_dash'           => ':attribute musí obsahovat pouze písmena, číslice a podtržítka.',
    'alpha_num'            => ':attribute musí obsahovat pouze písmena a číslice.',
    'array'                => ':attribute musí být pole.',
    'before'               => ':attribute musí být datum před :date.',
    'before_or_equal'      => ':attribute musí být datum před nebo rovný datu :date.',
    'between'              => [
        'numeric' => ':attribute musí být mezi :min a :max.',
        'file'    => ':attribute musí být mezi :min a :max kilobyty.',
        'string'  => ':attribute musí být mezi :min a :max znaky.',
        'array'   => ':attribute musí mít mezi :min a :max položkami.',
    ],
    'boolean'              => 'Pole :attribute musí být true nebo false.',
    'confirmed'            => 'Potvrzení :attribute se neshoduje.',
    'date'                 => ':attribute není platné datum.',
    'date_format'          => ':attribute se neshoduje s formátem :format.',
    'different'            => ':attribute a :other musí být jiné.',
    'digits'               => ':attribute musí mít :digits číslic.',
    'digits_between'       => ':attribute musí mít mezi :min a :max číslicemi.',
    'dimensions'           => ':attribute má neplatné rozměry obrázku.',
    'distinct'             => 'Pole :attribute má duplicitní hodnotu.',
    'email'                => ':attribute musí být platná e-mailová adresa.',
    'exists'               => 'Zvolený :attribute je neplatný.',
    'file'                 => ':attribute musí být soubor.',
    'filled'               => 'Pole :attribute musí mít hodnotu.',
    'image'                => ':attribute musí být obrázek.',
    'in'                   => 'Zvolený :attribute je neplatný.',
    'in_array'             => 'Pole :attribute neexistuje v :other.',
    'integer'              => ':attribute musí být celé číslo.',
    'ip'                   => ':attribute musí být platná IP adresa.',
    'ipv4'                 => ':attribute musí být platná IPv4 adresa.',
    'ipv6'                 => ':attribute musí být platná IPv6 adresa.',
    'json'                 => ':attribute musí být platný řetězec JSON.',
    'max'                  => [
        'numeric' => ':attribute nesmí být větší než :max.',
        'file'    => ':attribute nesmí být větší než :max kilobytů.',
        'string'  => ':attribute nesmí být větší než :max znaků.',
        'array'   => ':attribute nesmí mít více než :max položek.',
    ],
    'mimes'                => ':attribute musí být soubor typu: :values.',
    'mimetypes'            => ':attribute musí být soubor typu: :values.',
    'min'                  => [
        'numeric' => ':attribute musí být alespoň :min.',
        'file'    => ':attribute musí být alespoň :min kilobytů.',
        'string'  => ':attribute musí být alespoň :min znaků.',
        'array'   => ':attribute musí mít alespoň :min položek.',
    ],
    'not_in'               => 'Zvolený :attribute je neplatný.',
    'not_regex'            => 'Formát :attribute je neplatný.',
    'numeric'              => ':attribute musí být číslo.',
    'present'              => 'Pole :attribute musí být přítomné.',
    'regex'                => 'Formát :attribute je neplatný.',
    'required'             => 'Pole :attribute je vyžadováno.',
    'required_if'          => 'Pole :attribute je vyžadováno, pokud je :other :value.',
    'required_unless'      => 'Pole :attribute je vyžadováno, pokud není :other v :values.',
    'required_with'        => 'Pole :attribute je vyžadováno, pokud je přítomno :values.',
    'required_with_all'    => 'Pole :attribute je vyžadováno, pokud je přítomno :values.',
    'required_without'     => 'Pole :attribute je vyžadováno, pokud není přítomno :values.',
    'required_without_all' => 'Pole :attribute je vyžadováno, pokud není přítomno žádné z :values.',
    'same'                 => ':attribute a :other se musí shodovat.',
    'size'                 => [
        'numeric' => ':attribute musí být :size.',
        'file'    => ':attribute musí být :size kilobytů.',
        'string'  => ':attribute musí být :size znaků.',
        'array'   => ':attribute musí obsahovat :size položek.',
    ],
    'string'               => ':attribute musí být řetězec.',
    'timezone'             => ':attribute musí být platná zóna.',
    'unique'               => ':attribute je již zabráno.',
    'uploaded'             => 'Nahrávání :attribute selhalo.',
    'url'                  => 'Formát :attribute je neplatný.',

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
