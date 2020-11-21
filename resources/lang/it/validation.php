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

    'accepted'             => 'Il :attribute deve essere accettato.',
    'active_url'           => 'Il :attribute non è un URL valido.',
    'after'                => 'Il :attribute deve essere una data successiva a :date.',
    'after_or_equal'       => 'Il :attribute deve essere una data successiva o uguale a :date.',
    'alpha'                => 'Il :attribute può contenere solo lettere.',
    'alpha_dash'           => 'Il :attribute può contenere solo lettere, numeri e trattini.',
    'alpha_num'            => 'Il :attribute può contenere solo lettere e numeri.',
    'array'                => 'Il :attribute deve essere un array.',
    'before'               => 'Il :attribute deve essere una data precedente a :date.',
    'before_or_equal'      => 'Il :attribute deve essere una data precedente o uguale a :date.',
    'between'              => [
        'numeric' => 'Il :attribute deve essere compreso tra :min e :max.',
        'file'    => 'Il :attribute deve essere compreso tra :min e :max kilobytes.',
        'string'  => 'Il :attribute deve essere compreso tra :min e :max characters.',
        'array'   => 'Il :attribute deve essere compreso tra :min e :max items.',
    ],
    'boolean'              => 'Il :attribute campo deve essere vero o falso.',
    'confirmed'            => 'Il :attribute conferma non corrisponde.',
    'date'                 => 'Il :attribute non è una data valida.',
    'date_format'          => 'Il :attribute non corrisponde al formato :format.',
    'different'            => 'Il :attribute e :other devono essere diversi.',
    'digits'               => 'Il :attribute deve essere di :digits cifre.',
    'digits_between'       => 'Il :attribute deve essere compreso tra :min e :max cifre.',
    'dimensions'           => 'Il :attribute ha una dimensione di immagine non valida.',
    'distinct'             => 'Il :attribute campo ha un valore duplicato.',
    'email'                => 'Il :attribute deve essere un indirizzo e-mail valido.',
    'exists'               => 'Il selezionato :attribute non è valido.',
    'file'                 => 'Il :attribute deve essere un file.',
    'filled'               => 'Il :attribute campo deve avere un valore.',
    'image'                => 'Il :attribute deve essere una immagine.',
    'in'                   => 'Il selezionato :attribute non è valido.',
    'in_array'             => 'Il :attribute campo non esiste in :other.',
    'integer'              => 'Il :attribute deve essere un intero.',
    'ip'                   => 'Il :attribute deve essere un indirizzo IP valido.',
    'ipv4'                 => 'Il :attribute deve essere un indirizzo IPv4 valido.',
    'ipv6'                 => 'Il :attribute deve essere un indirizzo IPv6 valido.',
    'json'                 => 'Il :attribute deve essere una stringa JSON valida.',
    'max'                  => [
        'numeric' => 'Il :attribute non deve essere più grande di :max.',
        'file'    => 'Il :attribute non deve essere più grande di :max kilobytes.',
        'string'  => 'Il :attribute non deve essere più grande di :max caratteri.',
        'array'   => 'Il :attribute non deve avere più di :max oggetti.',
    ],
    'mimes'                => 'Il :attribute deve essere un file del tipo: :values.',
    'mimetypes'            => 'Il :attribute deve essere un file del tipo: :values.',
    'min'                  => [
        'numeric' => 'Il :attribute deve essere almeno :min.',
        'file'    => 'Il :attribute deve essere almeno :min kilobytes.',
        'string'  => 'Il :attribute deve essere almeno :min caratteri.',
        'array'   => 'Il :attribute deve avere almeno :min oggetti.',
    ],
    'not_in'               => 'Il selezionato :attribute non è valido.',
    'not_regex'            => 'Il :attribute formato non è valido.',
    'numeric'              => 'Il :attribute deve essere un numero.',
    'present'              => 'Il :attribute campo deve essere presente.',
    'regex'                => 'Il :attribute formato non è valido.',
    'required'             => 'Il :attribute campo è richiesto.',
    'required_if'          => 'Il :attribute campo è richiesto quando :other è :value.',
    'required_unless'      => 'Il :attribute campo è richiesto a meno che :other è in :values.',
    'required_with'        => 'Il :attribute campo è richiesto quando :values sono presenti.',
    'required_with_all'    => 'Il :attribute campo è richiesto quando :values sono presenti.',
    'required_without'     => 'Il :attribute campo è richiesto quando :values non sono presenti.',
    'required_without_all' => 'Il :attribute campo è richiesto quando nessuno dei :values sono presenti.',
    'same'                 => 'Il :attribute e :other devono corrispondere.',
    'size'                 => [
        'numeric' => 'Il :attribute deve essere :size.',
        'file'    => 'Il :attribute deve essere :size kilobytes.',
        'string'  => 'Il :attribute deve essere :size caratteri.',
        'array'   => 'Il :attribute deve contenere :size oggetti.',
    ],
    'string'               => 'Il :attribute deve essere una stringa.',
    'timezone'             => 'Il :attribute deve essere una zona valida.',
    'unique'               => 'Il :attribute è già stato preso.',
    'uploaded'             => 'Il :attribute non è stato caricato correttamente.',
    'url'                  => 'Il :attribute formato non è valido.',

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
            'rule-name' => 'messaggi-personalizzati',
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
