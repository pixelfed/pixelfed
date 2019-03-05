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

    'accepted'             => ':attribute 必須接受。',
    'active_url'           => ':attribute 不是有效的 URL。',
    'after'                => ':attribute 必須是在 :date 之後的日期。',
    'after_or_equal'       => ':attribute 必須是在 :date 當天或之後的日期。',
    'alpha'                => ':attribute 僅能包含字母。',
    'alpha_dash'           => ':attribute 僅能包含字母、數字與破折號。',
    'alpha_num'            => ':attribute 僅能包含字母與數字。',
    'array'                => ':attribute 必須為陣列。',
    'before'               => ':attribute 必須是在 :date 之前的日期。',
    'before_or_equal'      => ':attribute 必須是在 :date 當天或之前的日期。',
    'between'              => [
        'numeric' => ':attribute 必須在 :min 到 :max 之間。',
        'file'    => ':attribute 必須在 :min 到 :max KB 之間。',
        'string'  => ':attribute 必須在 :min 到 :max 個字母之間。',
        'array'   => ':attribute 必須在 :min 到 :max 個項目之間。',
    ],
    'boolean'              => ':attribute 欄位必須為真或假。',
    'confirmed'            => ':attribute 確認不符合。',
    'date'                 => ':attribute 不是有效的日期。',
    'date_format'          => ':attribute 不符合格式 :format。',
    'different'            => ':attribute 與 :other 必須不同。',
    'digits'               => ':attribute 必須有 :digits 位。',
    'digits_between'       => ':attribute 必須在 :min 到 :max 位之間。',
    'dimensions'           => ':attribute 圖片尺寸無效。',
    'distinct'             => ':attribute 欄位有重複的值。',
    'email'                => ':attribute 必須為有效的電子郵件地址。',
    'exists'               => '選定的 :attribute 無效。',
    'file'                 => ':attribute 必須為檔案。',
    'filled'               => ':attribute 欄位必須要有值。',
    'image'                => ':attribute 必須為圖片。',
    'in'                   => '選定的 :attribute 無效。',
    'in_array'             => ':attribute 欄位在 :other 中不存在。',
    'integer'              => ':attribute 必須為整數。',
    'ip'                   => ':attribute 必須為有效的 IP 位置。',
    'ipv4'                 => ':attribute 必須為有效的 IPv4 位置。',
    'ipv6'                 => ':attribute 必須為有效的 IPv6 位置。',
    'json'                 => ':attribute 必須為有效的 JSON 字串。',
    'max'                  => [
        'numeric' => ':attribute 不能大於 :max。',
        'file'    => ':attribute 不能大於 :max KB。',
        'string'  => ':attribute 不能多於 :max 個字元。',
        'array'   => ':attribute 不能多於 :max 個項目。',
    ],
    'mimes'                => ':attribute 必須為這些類型的檔案： :values。',
    'mimetypes'            => ':attribute 必須為這些類型的檔案： :values。',
    'min'                  => [
        'numeric' => ':attribute 必須至少 :min。',
        'file'    => ':attribute 必須至少 :min KB。',
        'string'  => ':attribute 必須至少 :min 個字元。',
        'array'   => ':attribute 必須至少 :min 個項目。',
    ],
    'not_in'               => '選定的 :attribute 無效。',
    'not_regex'            => ':attribute 格式無效。',
    'numeric'              => ':attribute 必須為數字。',
    'present'              => ':attribute 欄位必須存在。',
    'regex'                => ':attribute 格式無效。',
    'required'             => ':attribute 欄位必填。',
    'required_if'          => ':attribute 在 :other 為 :value 時必填。',
    'required_unless'      => ':attribute 欄位為必填，除非 :other 為 :values。',
    'required_with'        => '在 :values 存在時，:attribute 為必填。',
    'required_with_all'    => '在 :values 存在時，:attribute 為必填。',
    'required_without'     => '在 :values 不存在時，:attribute 為必填。',
    'required_without_all' => '在沒有 :values 存在時，:attribute 為必填。',
    'same'                 => ':attribute 與 :other 必須符合。',
    'size'                 => [
        'numeric' => ':attribute 必須為 :size。',
        'file'    => ':attribute 必須為 :size KB。',
        'string'  => ':attribute 必須有 :size 個字元。',
        'array'   => ':attribute 必須包含 :size 個項目。',
    ],
    'string'               => ':attribute 必須為字串。',
    'timezone'             => ':attribute 必須為有效的區域。',
    'unique'               => ':attribute 已被取用。',
    'uploaded'             => ':attribute 上傳失敗。',
    'url'                  => ':attribute 格式無效。',

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
            'rule-name' => '自訂訊息',
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
