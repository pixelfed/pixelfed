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

    'accepted'             => ':attribute を受け入れる必要があります。',
    'active_url'           => ':attribute は有効なURLではありません。',
    'after'                => ':attribute は :date 以降の日付である必要があります。',
    'after_or_equal'       => ':attribute は :date と同じかそれ以降の日付である必要があります。',
    'alpha'                => ':attribute には文字を含めることが出来ます。',
    'alpha_dash'           => ':attribute には文字、数字、ダッシュを含めることが出来ます。',
    'alpha_num'            => ':attribute には文字、または数字を含めることが出来ます。',
    'array'                => ':attribute は配列である必要があります。',
    'before'               => ':attribute は :date 以前の日付である必要があります。',
    'before_or_equal'      => ':attribute は :date と同じかそれ以前の日付である必要があります。',
    'between'              => [
        'numeric' => ':attribute は :min から :max の間である必要があります。',
        'file'    => ':attribute は :min から :max キロバイトの間である必要があります。',
        'string'  => ':attribute は :min から :max 文字の間である必要があります。',
        'array'   => ':attribute は :min アイテムから :max アイテムの間である必要があります。',
    ],
    'boolean'              => ':attribute フィールドは true か false である必要があります。',
    'confirmed'            => ':attribute の確認が一致しません。',
    'date'                 => ':attribute は有効な日付ではありません。',
    'date_format'          => ':attribute は :format と一致しません。',
    'different'            => ':attribute と :other は異なる必要があります。',
    'digits'               => ':attribute は :digits である必要があります。',
    'digits_between'       => ':attribute は :min から :max 間の数字である必要があります。',
    'dimensions'           => ':attribute は無効な画像サイズです。',
    'distinct'             => ':attribute フィールドに重複した値があります。',
    'email'                => ':attribute は有効なメールアドレスである必要があります。',
    'exists'               => '選択された :attribute は無効です。',
    'file'                 => ':attribute はファイルである必要があります。',
    'filled'               => ':attribute フィールドには値が必要です。',
    'image'                => ':attribute は画像である必要があります。',
    'in'                   => '選択された :attribute は無効です。',
    'in_array'             => ':attribute フィールドは :other には存在しません。',
    'integer'              => ':attribute は整数である必要があります。',
    'ip'                   => ':attribute は有効なIPアドレスである必要があります。',
    'ipv4'                 => ':attribute は有効なIPv4アドレスである必要があります。',
    'ipv6'                 => ':attribute は有効なIPv6アドレスである必要があります。',
    'json'                 => ':attribute は有効なJSON文字列である必要があります。',
    'max'                  => [
        'numeric' => ':attribute は :max 以下である必要があります。',
        'file'    => ':attribute は :max キロバイト以下である必要があります。',
        'string'  => ':attribute の文字数は :max 以下である必要があります。',
        'array'   => ':attribute は :max 以上のアイテム数を持つことは出来ません。',
    ],
    'mimes'                => ':attribute は :values タイプのファイルである必要があります。',
    'mimetypes'            => ':attribute は :values タイプのファイルである必要があります。',
    'min'                  => [
        'numeric' => ':attribute は最低でも :min 以上である必要があります。',
        'file'    => ':attribute は最低でも :min キロバイト以上である必要があります。',
        'string'  => ':attribute の文字数は最低でも :min 以上である必要があります。',
        'array'   => ':attribute は最低でも :min アイテム以上である必要があります。',
    ],
    'not_in'               => '選択された :attribute は無効です。',
    'not_regex'            => ':attribute は無効なフォーマットです。',
    'numeric'              => ':attribute は数字である必要があります。',
    'present'              => ':attribute フィールドは存在する必要があります。',
    'regex'                => ':attribute は無効なフォーマットです。',
    'required'             => ':attribute フィールドは必要です。',
    'required_if'          => ':other が :value の場合、 :attribute は必要です。',
    'required_unless'      => ':other が :values にない場合、 :attribute フィールドは必要です。',
    'required_with'        => ':values が存在する場合、 :attribute は必要です。',
    'required_with_all'    => ':values が存在する場合、 :attribute は必要です。',
    'required_without'     => ':values が存在しない場合、 :attribute は必要です。',
    'required_without_all' => ':values が一つも存在しない場合、 :attribute は必要です。',
    'same'                 => ':attribute と :other は一致する必要があります。',
    'size'                 => [
        'numeric' => ':attribute は :size である必要があります。',
        'file'    => ':attribute は :size キロバイトである必要があります。',
        'string'  => ':attribute の文字数は :size である必要があります。',
        'array'   => ':attribute には :size のアイテムが含まれている必要があります。',
    ],
    'string'               => ':attribute は文字列である必要があります。',
    'timezone'             => ':attribute は有効なゾーンである必要があります。',
    'unique'               => ':attribute は既に使用されています。',
    'uploaded'             => ':attribute のアップロードに失敗しました。',
    'url'                  => ':attribute は無効なフォーマットです。',

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
