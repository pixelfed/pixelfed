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
    
    'accepted'             => 'Rhaid derbyn y :attribute.',
    'active_url'           => 'Nid yw\'r :attribute yn URL dilys.',
    'after'                => 'Rhaid i\'r :attribute fod yn ddyddiad ar ôl :date.',
    'after_or_equal'       => 'Rhaid i\'r :attribute fod yn ddyddiad ar ôl neu\'n hafal i :date.',
    'alpha'                => 'Dim ond llythyrau y gall y :attribute eu cynnwys.',
    'alpha_dash'           => 'Dim ond llythrennau, rhifau a thaenau y gall y :attribute eu cynnwys.',
    'alpha_num'            => 'Dim ond llythrennau a rhifau y gall y :attribute eu cynnwys.',
    'array'                => 'Rhaid i\'r :attribute fod yn arae.',
    'before'               => 'Rhaid i\'r :attribute fod yn ddyddiad cyn :date.',
    'before_or_equal'      => 'Rhaid i\'r :attribute fod yn ddyddiad cyn neu\'n hafal i :date.',
    'between'              => [
        'numeric' => 'Rhaid i\'r :attribute fod rhwng :min a :max.',
        'file'    => 'Rhaid i\'r :attribute fod rhwng :min a :max cilobytes.',
        'string'  => 'Rhaid i\'r :attribute fod rhwng :min a :max nodau.',
        'array'   => 'Rhaid i\'r :attribute fod rhwng :min a :max eitem.',
    ],
    'boolean'              => 'Rhaid i\'r maes :attribute fod yn wir neu\'n anwir.',
    'confirmed'            => 'Nid yw\'r cadarnhad :attribute yn cyfateb.',
    'date'                 => 'Nid yw\'r :attribute yn ddyddiad dilys.',
    'date_format'          => 'Nid yw\'r :attribute yn cyd-fynd â\'r fformat :format.',
    'different'            => 'Rhaid i\'r :attribute a\'r :other fod yn wahanol.',
    'digits'               => 'Rhaid i\'r :attribute fod yn :digits digid.',
    'digits_between'       => 'Rhaid i\'r :attribute fod rhwng :min a :max digid.',
    'dimensions'           => 'Mae gan y :attribute ddimensiynau delwedd annilys.',
    'distinct'             => 'Mae gan y maes :attribute werthoedd dyblyg.',
    'email'                => 'Rhaid i\'r :attribute fod yn gyfeiriad e-bost dilys.',
    'exists'               => 'Mae\'r :attribute a ddewiswyd yn annilys.',
    'file'                 => 'Rhaid i\'r :attribute fod yn ffeil.',
    'filled'               => 'Rhaid bod gwerth i\'r maes :attribute.',
    'image'                => 'Rhaid i\'r :attribute fod yn ddelwedd.',
    'in'                   => 'Mae\'r :attribute a ddewiswyd yn annilys.',
    'in_array'             => 'Nid yw\'r maes :attribute yn bodoli yn :other.',
    'integer'              => 'Rhaid i\'r :attribute fod yn gyfanrif.',
    'ip'                   => 'Rhaid i\'r :attribute fod yn gyfeiriad IP dilys.',
    'ipv4'                 => 'Rhaid i\'r :attribute fod yn gyfeiriad IPv4 dilys.',
    'ipv6'                 => 'Rhaid i\'r :attribute fod yn gyfeiriad IPv6 dilys.',
    'json'                 => 'Rhaid i\'r :attribute fod yn llinyn JSON dilys.',
    'max'                  => [
        'numeric' => 'Efallai na fydd y :attribute yn fwy na :max.',
        'file'    => 'Efallai na fydd y :attribute yn fwy na :max cilobytes.',
        'string'  => 'Efallai na fydd y :attribute yn fwy na :max nodau.',
        'array'   => 'Efallai na fydd y :attribute yn fwy na :max eitem.',
    ],
    'mimes'                => 'Rhaid i\'r :attribute fod yn ffeil o fath: :values.',
    'mimetypes'            => 'Rhaid i\'r :attribute fod yn ffeil o fath: :values.',
    'min'                  => [
        'numeric' => 'Rhaid i\'r :attribute fod o leiaf :min.',
        'file'    => 'Rhaid i\'r :attribute fod o leiaf :min cilobytes.',
        'string'  => 'Rhaid i\'r :attribute fod o leiaf :min nodau.',
        'array'   => 'Rhaid i\'r :attribute fod o leiaf :min eitem.',
    ],
    'not_in'               => 'The selected :attribute is invalid.',
    'not_regex'            => 'The :attribute format is invalid.',
    'numeric'              => 'The :attribute must be a number.',
    'present'              => 'The :attribute field must be present.',
    'regex'                => 'The :attribute format is invalid.',
    'required'             => 'The :attribute field is required.',
    'required_if'          => 'The :attribute field is required when :other is :value.',
    'required_unless'      => 'The :attribute field is required unless :other is in :values.',
    'required_with'        => 'The :attribute field is required when :values is present.',
    'required_with_all'    => 'The :attribute field is required when :values is present.',
    'required_without'     => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same'                 => 'The :attribute and :other must match.',
    'size'                 => [
        'numeric' => ':attribute rhaid bod :size.',
        'file'    => ':attribute rhaid bod :size cilobytes.',
        'string'  => ':attribute rhaid bod :size nodau.',
        'array'   => ':attribute rhaid bod :size eitem.',
    ],
    'string'               => ':attribute rhaid fod yn llinyn.',
    'timezone'             => ':attribute rhaid fod yn barth dilys.',
    'unique'               => ':attribute eisoes wedi\'u cymryd.',
    'uploaded'             => ':attribute wedi methu llwytho i fyny.',
    'url'                  => ':attribute fformat yn annilys.',
    
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
