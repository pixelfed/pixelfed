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

    'accepted'             => ':attribute kabul edilebilir olmalıdır.',
    'active_url'           => ':attribute geçerli bir URL değil.',
    'after'                => ':attribute tarihi :date tarihinden sonra olmalıdır.',
    'after_or_equal'       => ':attribute tarihi :date tarihine eşit veya sonrası olmalıdır.',
    'alpha'                => ':attribute sadece harf içerebilir.',
    'alpha_dash'           => ':attribute sadece harf, sayı ve tire içerebilir.',
    'alpha_num'            => ':attribute sadece harf ve sayı içerebilir.',
    'array'                => ':attribute bir dizi olmalıdır.',
    'before'               => ':attribute taihi :date tarihinden önce olmalıdır.',
    'before_or_equal'      => ':attribute tarihi :date tarihine eşit veya sonrası olmalıdır.',
    'between'              => [
        'numeric' => ':attribute, :min ile :max arasında olmalıdır.',
        'file'    => ':attribute, :min ile :max kilobayt arasında olmalıdır.',
        'string'  => ':attribute, :min ile :max karakter arasında olmalıdır.',
        'array'   => ':attribute, :min ile :max arasında elemanı olmalıdır..',
    ],
    'boolean'              => ':attribute doğru veya yanlış olmalıdır.',
    'confirmed'            => ':attribute doğrulama eşleşmedi.',
    'date'                 => ':attribute geçerli bir tarih değil.',
    'date_format'          => ':attribute biçim düzeni uyuşmuyor :format.',
    'different'            => ':attribute ve :other farklı olmalıdır.',
    'digits'               => ':attribute, :digits basamaklı olmalıdır.',
    'digits_between'       => ':attribute, :min ile :max basamak arasında olmalıdır.',
    'dimensions'           => ':attribute geçersiz görüntü ölçülerine sahip.',
    'distinct'             => ':attribute kopyalanabilir olmalıdır.',
    'email'                => ':attribute geçerli bir eposta adresi olmalıdır.',
    'exists'               => ':attribute geçersiz.',
    'file'                 => ':attribute bir dosya olmalıdır.',
    'filled'               => ':attribute bir değere sahip olmalıdır.',
    'image'                => ':attribute bir görüntü olmalıdır.',
    'in'                   => ':attribute geçersiz.',
    'in_array'             => ':attribute, :other içerisinde bulunmuyor.',
    'integer'              => ':attribute bir tam sayı olmalıdır.',
    'ip'                   => ':attribute geçerli bir IP adresi olmalıdır.',
    'ipv4'                 => ':attribute geçerli bir IPv4 adresi olmalıdır.',
    'ipv6'                 => ':attribute geçerli bir IPv6 adresi olmalıdır.',
    'json'                 => ':attribute geçerli bir JSON metini olmalıdır.',
    'max'                  => [
        'numeric' => ':attribute, :max değerinden büyük olamaz.',
        'file'    => ':attribute, :max kilobayt\'dan büyük olamaz.',
        'string'  => ':attribute, :max karakterden büyük olamaz.',
        'array'   => ':attribute, :max elemandan fazla olamaz.',
    ],
    'mimes'                => ':attribute şu dosya tiplerinde olabilir: :values.',
    'mimetypes'            => ':attribute şu dosya tiplerinde olabilir: :values.',
    'min'                  => [
        'numeric' => ':attribute en az :min olabilir.',
        'file'    => ':attribute en az :min kilobayt olabilir.',
        'string'  => ':attribute en az :min karakter olabilir.',
        'array'   => ':attribute en az :min eleman olabilir.',
    ],
    'not_in'               => ':attribute geçersiz.',
    'not_regex'            => ':attribute biçimi geçersiz.',
    'numeric'              => ':attribute bir sayı olmalıdır.',
    'present'              => ':attribute alanı şu an\'ı belirtmelidir.',
    'regex'                => ':attribute biçimi geçersiz.',
    'required'             => ':attribute alanı gereklidir.',
    'required_if'          => ':attribute, :other :value değerine eşit olduğu zaman gereklidir.',
    'required_unless'      => ':attribute, :other :values içinde olmadıkça gereklidir.',
    'required_with'        => ':attribute, :values mevcut ise gereklidir.',
    'required_with_all'    => ':attribute, :values mevcut ise gereklidir.',
    'required_without'     => ':attribute, :values mevcut değil ise gereklidir.',
    'required_without_all' => ':attribute, :values olmadığında gereklidir.',
    'same'                 => ':attribute ve :other eşleşmelidir.',
    'size'                 => [
        'numeric' => ':attribute, :size boyutunda olmalıdır.',
        'file'    => ':attribute, :size kilobayt boyutunda olmalıdır.',
        'string'  => ':attribute, :size karakter olmalıdır.',
        'array'   => ':attribute, :size eleman olmalıdır.',
    ],
    'string'               => ':attribute metin olmalıdır.',
    'timezone'             => ':attribute geçerli bir saat dilimi olmalıdır.',
    'unique'               => ':attribute çoktan alınmış.',
    'uploaded'             => ':attribute yüklerken hata oluştu.',
    'url'                  => ':attribute biçim geçersiz.',

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
