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

    'accepted'             => ':attribute onartuak izan behar dira.',
    'active_url'           => ':attribute helbideak ez du zuzena.',
    'after'                => ':attribute datak :date baino beranduagokoa izan behar du.',
    'after_or_equal'       => ':attribute datak :date baino beranduagokoa edo berdina izan behar du.',
    'alpha'                => ':attribute -(e)k letrak bakarrik izan ditzake',
    'alpha_dash'           => ':attribute -(e)k letrak, zenbakiak eta gidoiak bakarrik onartzen ditu.',
    'alpha_num'            => ':attribute -(e)k letrak eta zenbakiak bakarrik onartzen ditu',
    'array'                => ':attribute zerrenda izan behar da.',
    'before'               => ':attribute datak :date baino lehenagokoa izan behar du.',
    'before_or_equal'      => ':attribute datak :date baino lehenagokoa edo berdina izan behar du.',
    'between'              => [
        'numeric' => ':attribute :min eta :max -ren artean izan behar da.',
        'file'    => ':attribute :min eta :max kilobytes artean izan behar da.',
        'string'  => ':attribute -(e)k :min eta :max karaktere bitartean izan behar ditu.',
        'array'   => ':attribute -(e)k :min eta :max elementu bitartean izan behar ditu.',
    ],
    'boolean'              => ':attribute eremua "True" edo "False" izan behar da.',
    'confirmed'            => ':attribute-ren baieztapenak ez datoz bat.',
    'date'                 => ':attribute ez da data formatu egokia.',
    'date_format'          => ':attribute -(e)k ez du :format formatua betetzen.',
    'different'            => ':attribute eta :other ezberdinak izan behar dira.',
    'digits'               => ':attribute -(e)k :digits zifra izan behar ditu.',
    'digits_between'       => ':attribute -(e)k :min eta :max zifra bitartean izan behar ditu.',
    'dimensions'           => ':attribute -(e)k onartzen ez diren tamainak ditu.',
    'distinct'             => ':attribute eremuak bikoiztutako balorea du.',
    'email'                => ':attribute eremua e-posta izan behar da.',
    'exists'               => 'Aukeratutako :attribute ez da baliozkoa.',
    'file'                 => ':attribute eremua fitxategia izan behar da.',
    'filled'               => ':attribute eremuak balio egokia izan behar du.',
    'image'                => ':attribute eremua irudia izan behar da.',
    'in'                   => 'Aukeratutako :attribute elementua ez da baliozkoa.',
    'in_array'             => ':attribute eremua ez da :other -en existitzen.',
    'integer'              => ':attribute zenbaki osoa izen behar da.',
    'ip'                   => ':attribute eremua IP helbide egokia izan behar da.',
    'ipv4'                 => ':attribute eremua IPv4 helbide egokia izan behar da.',
    'ipv6'                 => ':attribute eremua IPv6 helbide egokia izan behar da.',
    'json'                 => ':attribute eremuak JSON egokia izan behar du.',
    'max'                  => [
        'numeric' => ':attribute ezin da :max baino handiagoa izan.',
        'file'    => ':attribute -(e)k ezin du :max kilobyte baino gehiago izan.',
        'string'  => ':attribute -(e)k ezin ditu :max karaktere baino gehiago izan.',
        'array'   => ':attribute -(e)k ezin ditu :max elementu baino gehiago izan.',
    ],
    'mimes'                => ':attribute :values motatako fitxategia izan behar da.',
    'mimetypes'            => ':attribute :values motatako fitxategia izan behar da.',
    'min'                  => [
        'numeric' => ':attribute gutxienez :min izan behar da.',
        'file'    => ':attribute gutxienez :min kilobyte izan behar ditu.',
        'string'  => ':attribute gutxienez :min karaktere izan behar ditu.',
        'array'   => ':attribute -(e)k gutxienez :min elementu izan behar ditu.',
    ],
    'not_in'               => 'Aukeratutako :attribute elementuak ez du balio.',
    'not_regex'            => ':attribute formatua ez da zuzena.',
    'numeric'              => ':attribute zenbakia izan behar da.',
    'present'              => ':attribute eremua egon behar da.',
    'regex'                => ':attribute -ren formatua ez da zuzena.',
    'required'             => ':attribute eremua derrigorrezkoa da.',
    'required_if'          => ':attribute eremua derrigorrezkoa da :other :value denean.',
    'required_unless'      => ':attribute eremua derrigorrezkoa da :other :values izan ezean.',
    'required_with'        => ':values dagoenean, :attribute eremua derrigorrezkoa da.',
    'required_with_all'    => ':values dagoenean, :attribute eremua derrigorrezkoa da.',
    'required_without'     => ':attribute eremua derrigorrezkoa da :values eremua ez dagoenean.',    
    'required_without_all' => ':attribute eremua beharrezkoa da :values bat bera ere ez dagoenean.',
    'same'                 => ':attribute eta :other bat etorri behar dira.',
    'size'                 => [
        'numeric' => ':attribute :size izan behar da.',
        'file'    => ':attribute -(e)k :size kilobyte izan behar ditu.',
        'string'  => ':attribute -(e)k :size karaktere izan behar ditu.',
        'array'   => ':attribute -(e)k :size elementu izan behar ditu.',
    ],
    'string'               => ':attribute testua izan behar da.',
    'timezone'             => ':attribute ordutegi zuzena izan behar du.',
    'unique'               => ':attribute ez dago eskuragarri.',
    'uploaded'             => ':attribute igotzerakoan akatsa.',
    'url'                  => ':attribute -ren formatua ez da egokia.',

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
