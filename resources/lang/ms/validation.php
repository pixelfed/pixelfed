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

    'accepted'             => ':attribute haruslah diterima.',
    'active_url'           => ':attribute bukan URL yang sah.',
    'after'                => ':attribute tarikh mestilah selepas :date.',
    'after_or_equal'       => ':attribute mestilah tarikh selepas atau sama dengan :date.',
    'alpha'                => ':attribute mesti mempunyai abjad sahaja.',
    'alpha_dash'           => ':attribute mesti mempunyai abjad, nombor, dan sengkang sahaja.',
    'alpha_num'            => ':attribute mesti mempunyai abjad dan nombor sahaja.',
    'array'                => ':attribute mestilah lokasi.',
    'before'               => ':attribute mestilah tarikh sebelum :date.',
    'before_or_equal'      => ':attribute mestilah tarikh sebelum atau sama dengan :date.',
    'between'              => [
        'numeric' => ':attribute mestilah antara :min dan :max.',
        'file'    => ':attribute mestilah antara :min dan :max kilobytes.',
        'string'  => ':attribute mestilah antara :min dan :max perkataan.',
        'array'   => ':attribute mestilah antara :min dan :max item.',
    ],
    'boolean'              => ':attribute mestilah ruang betul atau salah.',
    'confirmed'            => ':attribute pengesahan tidak sepadan.',
    'date'                 => ':attribute bukan tarikh yang sah.',
    'date_format'          => ':attribute tidak sepadan dengan format :format.',
    'different'            => ':attribute dan :other mestilah berbeza.',
    'digits'               => ':attribute mestilah :digits digit.',
    'digits_between'       => ':attribute mestilah antara :min dan :max digit.',
    'dimensions'           => ':attribute mempunyai dimensi imej yang tidak sah.',
    'distinct'             => ':attribute ruang mempunyai nilai pendua.',
    'email'                => ':attribute mestilah alamat emel yang sah.',
    'exists'               => ':attribute yang dipilih tidak sah.',
    'file'                 => ':attribute mestilah sebuah fail.',
    'filled'               => ':attribute ruang mestilah mempunyai nilai.',
    'image'                => ':attribute mestilah imej.',
    'in'                   => ':attribute yang di pilih tidak sah.',
    'in_array'             => ':attribute ruang tidak wujud dalam :other.',
    'integer'              => ':attribute mestilah integer.',
    'ip'                   => ':attribute mestilah alamat IP yang sah.',
    'ipv4'                 => ':attribute mestilah alamat IPv4 yang sah.',
    'ipv6'                 => ':attribute mestilah alamat IPv6 yang sah.',
    'json'                 => ':attribute mestilah rentetan JSON yang sah.',
    'max'                  => [
        'numeric' => ':attribute mesti tidak besar daripada :max.',
        'file'    => ':attribute mesti tidak besar daripada :max kilobytes.',
        'string'  => ':attribute mesti tidak besar daripada :max perkataan.',
        'array'   => ':attribute mesti tidak lebih daripada :max item.',
    ],
    'mimes'                => ':attribute mestilah mempunyai jenis file: :values.',
    'mimetypes'            => ':attribute mestilah mempunyai jenis file: :values.',
    'min'                  => [
        'numeric' => ':attribute mesti sekurang-kurangnya :min.',
        'file'    => ':attribute mesti sekurang-kurangnya :min kilobytes.',
        'string'  => ':attribute mesti sekurang-kurangnya :min perkataan.',
        'array'   => ':attribute mesti mempunyai sekurang-kurangnya :min item.',
    ],
    'not_in'               => ':attribute yang di pilih tidak sah.',
    'not_regex'            => ':attribute adalah format tidak sah.',
    'numeric'              => ':attribute mestilah nombor.',
    'present'              => ':attribute ruang mesti ada.',
    'regex'                => ':attribute adalah format tidak sah.',
    'required'             => ':attribute ruang diperlukan.',
    'required_if'          => ':attribute ruang diperlukan bila :other adalah :value.',
    'required_unless'      => ':attribute ruang diperlukan kecuali jika :other ada dalam :values.',
    'required_with'        => 'The :attribute ruang diperlukan bila :values wujud.',
    'required_with_all'    => 'The :attribute ruang diperlukan bila :values wujud.',
    'required_without'     => 'The :attribute ruang diperlukan bila :values tidak wujud.',
    'required_without_all' => 'The :attribute ruang diperlukan bila tiada daripada :values wujud.',
    'same'                 => ':attribute dan :other mestilah sepadan.',
    'size'                 => [
        'numeric' => ':attribute mesilah :size.',
        'file'    => ':attribute mestilah :size kilobytes.',
        'string'  => ':attribute mestilah :size perkataan.',
        'array'   => ':attribute mesti mempunyai :size item.',
    ],
    'string'               => ':attribute mestilah rentetan.',
    'timezone'             => ':attribute mesti zon waktu yang sah.',
    'unique'               => ':attribute telah diambil.',
    'uploaded'             => ':attribute gagal dimuat naik.',
    'url'                  => ':attribute adalah format tidak sah.',

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
