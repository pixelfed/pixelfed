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

    'accepted'             => 'La :attribute devas esti akceptita.',
    'active_url'           => 'La :attribute ne estas valida URL.',
    'after'                => 'La :attribute devas esti dato post :date.',
    'after_or_equal'       => 'La :attribute devas esti dato post aŭ la sama kiel :date.',
    'alpha'                => 'La :attribute devas enhavi nur literoj.',
    'alpha_dash'           => 'La :attribute devas enhavi nur literoj, ciferoj, kaj streketoj.',
    'alpha_num'            => 'La :attribute devas enhavi nur literoj kaj ciferoj.',
    'array'                => 'La :attribute devas esti tabelo.',
    'before'               => 'La :attribute devas esti dato antaŭ :date.',
    'before_or_equal'      => 'La :attribute devas esti dato antaŭ aŭ la sama kiel :date.',
    'between'              => [
        'numeric' => 'La :attribute devas esti inter :min kaj :max.',
        'file'    => 'La :attribute devas esti inter :min kaj :max kilobajtoj.',
        'string'  => 'La :attribute devas esti inter :min kaj :max signoj.',
        'array'   => 'La :attribute devas havi inter :min kaj :max eroj.',
    ],
    'boolean'              => 'La :attribute kampo devas esti vera aŭ malvera.',
    'confirmed'            => 'La :attribute konfirmo ne kongruas.',
    'date'                 => 'La :attribute ne estas validan daton.',
    'date_format'          => 'La :attribute ne kongruas la strukturon :format.',
    'different'            => 'La :attribute kaj :other devas esti malsama.',
    'digits'               => 'La :attribute devas esti :digits ciferoj.',
    'digits_between'       => 'La :attribute devas esti inter :min kaj :max ciferoj.',
    'dimensions'           => 'La :attribute havas malvalidan bildan dimensiojn.',
    'distinct'             => 'La :attribute kampo havas duoblan valoron.',
    'email'                => 'La :attribute devas esti valida retadreso.',
    'exists'               => 'La elektita :attribute estas malvalida.',
    'file'                 => 'La :attribute devas esti dosiejro.',
    'filled'               => 'La :attribute kampo devas havi valoro.',
    'image'                => 'La :attribute devas esti bildo.',
    'in'                   => 'La elektita :attribute estas malvalida.',
    'in_array'             => 'La :attribute kampo ne ekzistas en :other.',
    'integer'              => 'La :attribute devas esti entjero.',
    'ip'                   => 'La :attribute devas esti valida IP-adreso.',
    'ipv4'                 => 'La :attribute devas esti valida IPv4-adreso.',
    'ipv6'                 => 'La :attribute devas esti valida IPv6-adreso.',
    'json'                 => 'La :attribute devas esti valida JSON-signoĉeno.',
    'max'                  => [
        'numeric' => 'La :attribute ne devas esti pri granda ol :max.',
        'file'    => 'La :attribute ne devas esti pri granda ol :max kilobajtoj.',
        'string'  => 'La :attribute ne devas esti pri granda ol :max signoj.',
        'array'   => 'La :attribute ne devas havi pli ol :max eroj.',
    ],
    'mimes'                => 'La :attribute devas esti dosiejro de tipo: :values.',
    'mimetypes'            => 'La :attribute devas esti dosierjo de tipo: :values.',
    'min'                  => [
        'numeric' => 'La :attribute devas esti minimume :min.',
        'file'    => 'La :attribute devas esti minimume :min kilobajtoj.',
        'string'  => 'La :attribute devas esti minimume :min signoj.',
        'array'   => 'La :attribute devas havi minimume :min eroj.',
    ],
    'not_in'               => 'La elektita :attribute estas malvalida.',
    'not_regex'            => 'La :attribute strukturo estas malvalida.',
    'numeric'              => 'La :attribute devas esti ciferoj.',
    'present'              => 'La :attribute kampo devas ĉeesti.',
    'regex'                => 'La :attribute strukturo estas malvalida.',
    'required'             => 'La :attribute kampo estas bezonata.',
    'required_if'          => 'La :attribute kampo estas bezonata kiam :other estas :value.',
    'required_unless'      => 'La :attribute kampo estas bezonata krom se :other estas en :values.',
    'required_with'        => 'La :attribute kampo estas bezonata kiam :values ĉeestas.',
    'required_with_all'    => 'La :attribute kampo estas bezonata kiam :values ĉeestas.',
    'required_without'     => 'La :attribute kampo estas bezonata kiam :values ne ĉeestas.',
    'required_without_all' => 'La :attribute kampo estas bezonata kiam neniu el :values ĉeestas.',
    'same'                 => 'La :attribute kaj :other devas kongrui.',
    'size'                 => [
        'numeric' => 'La :attribute devas esti :size.',
        'file'    => 'La :attribute devas esti :size kilobajtoj.',
        'string'  => 'La :attribute devas esti :size signoj.',
        'array'   => 'La :attribute devas havi :size eroj.',
    ],
    'string'               => 'La :attribute devas esti signoĉeno.',
    'timezone'             => 'La :attribute devas esti valida zono.',
    'unique'               => 'La :attribute jam estis prenita.',
    'uploaded'             => 'La :attribute ne povis alŝuti.',
    'url'                  => 'La :attribute strukturo estas malvalida.',

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
