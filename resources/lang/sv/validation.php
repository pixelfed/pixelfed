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

    'accepted'             => ':attribute måste accepteras.',
    'active_url'           => ':attribute är inte en giltig URL.',
    'after'                => ':attribute måste vara ett datum efter :date.',
    'after_or_equal'       => ':attribute måste vara ett datum efter eller samma som :date.',
    'alpha'                => ':attribute får endast innehålla bokstäver.',
    'alpha_dash'           => ':attribute får endast innehålla bokstäver, nummer, och bindestreck.',
    'alpha_num'            => ':attribute får endast innehålla bokstäver och nummer.',
    'array'                => ':attribute måste vara en array.',
    'before'               => ':attribute måste vara ett datum före :date.',
    'before_or_equal'      => ':attribute måste vara ett datum före eller samma som :date.',
    'between'              => [
        'numeric' => ':attribute måste vara mellan :min och :max.',
        'file'    => ':attribute måste vara mellan :min och :max kilobytes.',
        'string'  => ':attribute måste vara mellan :min och :max tecken.',
        'array'   => ':attribute måste ha mellan :min och :max objekt.',
    ],
    'boolean'              => ':attribute fält måste vara sant eller falskt.',
    'confirmed'            => ':attribute bekräftelse stämmer inte.',
    'date'                 => ':attribute är inte ett giltigt datum.',
    'date_format'          => ':attribute matchar inte formatet :format.',
    'different'            => ':attribute och :other måste vara olika.',
    'digits'               => ':attribute måste vara :digits siffror.',
    'digits_between'       => ':attribute måste vara mellan :min och :max siffror.',
    'dimensions'           => ':attribute har ogiltiga bilddimensioner.',
    'distinct'             => ':attribute fält har ett duplikatvärde.',
    'email'                => ':attribute måste vara en giltig e-postadress.',
    'exists'               => 'vald :attribute är ogiltig.',
    'file'                 => ':attribute måste vara en fil.',
    'filled'               => ':attribute fält måste ha ett värde.',
    'image'                => ':attribute måste vara en bild.',
    'in'                   => 'vald :attribute är ogiltig.',
    'in_array'             => ':attribute fält existerar inte i :other.',
    'integer'              => ':attribute måste vara ett heltal.',
    'ip'                   => ':attribute måste vara en giltig IP-adress.',
    'ipv4'                 => ':attribute måste vara en giltig IPv4 adress.',
    'ipv6'                 => ':attribute måste vara en giltig IPv6 adress.',
    'json'                 => ':attribute måste vara en giltig JSON string.',
    'max'                  => [
        'numeric' => ':attribute får inte vara större än :max.',
        'file'    => ':attribute får inte vara större än :max kilobyte.',
        'string'  => ':attribute får inte vara större än :max tecken.',
        'array'   => ':attribute får inte ha fler än :max objekt.',
    ],
    'mimes'                => ':attribute måste vara en fil av typ: :values.',
    'mimetypes'            => ':attribute måste vara en fil av typ: :values.',
    'min'                  => [
        'numeric' => ':attribute måste vara åtminstone :min.',
        'file'    => ':attribute måste vara åtminstone :min kilobyte.',
        'string'  => ':attribute måste vara åtminstone :min tecken.',
        'array'   => ':attribute måste innehålla åtminstone :min objekt.',
    ],
    'not_in'               => 'vald :attribute är ogiltig.',
    'not_regex'            => ':attribute formatet är ogiltigt.',
    'numeric'              => ':attribute måste vara ett nummer.',
    'present'              => ':attribute fält måste finnas.',
    'regex'                => ':attribute formatet är ogiltigt.',
    'required'             => ':attribute fält krävs.',
    'required_if'          => ':attribute fält krävs när :other är :value.',
    'required_unless'      => ':attribute fält krävs om inte :other är i :values.',
    'required_with'        => ':attribute fält krävs när :values finns.',
    'required_with_all'    => ':attribute fält krävs när :values finns.',
    'required_without'     => ':attribute fält krävs när :values inte finns.',
    'required_without_all' => ':attribute fält krävs när inga av :values finns.',
    'same'                 => ':attribute och :other måste matcha.',
    'size'                 => [
        'numeric' => ':attribute måste vara :size.',
        'file'    => ':attribute måste vara :size kilobyte.',
        'string'  => ':attribute måste vara :size tecken.',
        'array'   => ':attribute måste innehålla :size objekt.',
    ],
    'string'               => ':attribute måste vara en string.',
    'timezone'             => ':attribute måste vara en giltig zon.',
    'unique'               => ':attribute är redan taget.',
    'uploaded'             => 'uppladdning av :attribute misslyckades.',
    'url'                  => 'format av :attribute är ogiltigt.',

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
