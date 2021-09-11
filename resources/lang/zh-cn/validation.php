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

    'accepted'             => ':attribute 必须接受',
    'active_url'           => ':attribute 不是有效的url。',
    'after'                => ':attribute 必须是在 :date 之后的日期。',
    'after_or_equal'       => ':attribute 必须是在 :date 当天或之后的日期.',
    'alpha'                => ':attribute 只能包含字母',
    'alpha_dash'           => ':attribute 只能包含字母、数字和破折号。',
    'alpha_num'            => ':attribute 只能包含字母和数字',
    'array'                => ':attribute 必须是数组',
    'before'               => ':attribute 必须是在 :date 之前的日期',
    'before_or_equal'      => ':attribute 必须是在 :date 当天或之前的日期',
    'between'              => [
        'numeric' => ':attribute 必须在 :min 到 :max 之间',
        'file'    => ':attribute 必须在 :min 到 :max KB 之间.',
        'string'  => ':attribute 必须在 :min 到 :max 个字母之间.',
        'array'   => ':attribute 必须在 :min 到 :max 个元素之间',
    ],
    'boolean'              => ':attribute 字段必须为真或假。',
    'confirmed'            => ':attribute 验证不匹配',
    'date'                 => ':attribute 不是有效日期',
    'date_format'          => ':attribute 不符合格式 :format',
    'different'            => ':attribute 和 :other 必须不同',
    'digits'               => ':attribute 必须有 :digits 位',
    'digits_between'       => ':attribute 必须在 :min 到 :max 位之间',
    'dimensions'           => ':attribute 图片尺寸无效',
    'distinct'             => ':attribute 字段有重复的值。',
    'email'                => ':attribute 必须是有效的邮件地址',
    'exists'               => '选择的 :attribute 无效',
    'file'                 => ':attribute 必须是文件。',
    'filled'               => ':attribute 字段必须有值。',
    'image'                => ':attribute 必须是图像。',
    'in'                   => '选定的 :attribute 无效。',
    'in_array'             => ':attribute 字段在 :other 中不存在',
    'integer'              => ':attribute 必须是整数。',
    'ip'                   => ':attribute 必须是有效的 IP 地址',
    'ipv4'                 => ':attribute 必须是有效的 IPv4 地址。',
    'ipv6'                 => ':attribute 必须是有效的 IPv6 地址。',
    'json'                 => ':attribute 必须是有效的 JSON 字符串。',
    'max'                  => [
        'numeric' => ':attribute 不能大于 :max',
        'file'    => ':attribute 不能大于 :max KB',
        'string'  => ':attribute 不能多于 :max 个字符',
        'array'   => ':attribute 不能多于 :max 个项目',
    ],
    'mimes'                => ':attribute 必须为该类型的文件: :values.',
    'mimetypes'            => ':attribute 必须为该类型的文件: :values.',
    'min'                  => [
        'numeric' => ':attribute 必须至少 :min',
        'file'    => ':attribute 必须至少 :min KB',
        'string'  => ':attribute 必须至少 :min 个字符',
        'array'   => ':attribute 必须至少 :min 个项目',
    ],
    'not_in'               => '选定的 :attribute 无效。',
    'not_regex'            => ':attribute 格式无效。',
    'numeric'              => ':attribute 必须是数字。',
    'present'              => ':attribute 字段必须存在',
    'regex'                => ':attribute 格式无效。',
    'required'             => ':attribute 字段是必填的。',
    'required_if'          => ':attribute 字段在 :other 为 :value 时是必填的。',
    'required_unless'      => ':attribute 字段是必填的，除非 :other 为 :values。',
    'required_with'        => '当 :values 存在时, :attribute 字段是必填的。',
    'required_with_all'    => '当 :values 存在时, :attribute 字段是必填的。',
    'required_without'     => '当 :values 不存在时, :attribute 字段是必填的。',
    'required_without_all' => '当没有 :values 存在时, :attribute 字段是必填的。',
    'same'                 => ':attribute 和 :other 必须相同',
    'size'                 => [
        'numeric' => ':attribute 必须是: :size.',
        'file'    => ':attribute 必须是: :size KB.',
        'string'  => ':attribute 必须是: :size 个字符',
        'array'   => ':attribute 必须包含: :size 个项目',
    ],
    'string'               => ':attribute 必须是字符串。',
    'timezone'             => ':attribute 必须是有效的区域。',
    'unique'               => ':attribute 已被使用。',
    'uploaded'             => ':attribute 上传失败。',
    'url'                  => ':attribute 格式无效。',

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
            'rule-name' => '自定义消息',
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
