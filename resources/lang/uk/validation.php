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

    'accepted'             => 'Потрібно прийняти :attribute.',
    'active_url'           => ':attribute — хибна URL-адреса.',
    'after'                => ':attribute мусить бути пізніше, ніж :date.',
    'after_or_equal'       => ':attribute мусить бути не раніше, ніж :date.',
    'alpha'                => ':attribute може містити лише літери.',
    'alpha_dash'           => ':attribute може містити лише літери, цифри й дефіси.',
    'alpha_num'            => ':attribute може містити лише літери й цифри.',
    'array'                => ':attribute мусить бути масивом.',
    'before'               => ':attribute мусить бути раніше, ніж :date.',
    'before_or_equal'      => ':attribute мусить бути не пізніше, ніж :date.',
    'between'              => [
        'numeric' => ':attribute мусить бути між :min і :max.',
        'file'    => ':attribute мусить містити від :min до :max кілобайтів.',
        'string'  => ':attribute мусить містити від :min до :max знаків.',
        'array'   => ':attribute мусить містити від :min до :max пунктів.',
    ],
    'boolean'              => 'Поле :attribute мусить бути істинним чи хибним.',
    'confirmed'            => 'Підтвердження :attribute не збігається.',
    'date'                 => ':attribute — хибна дата.',
    'date_format'          => ':attribute не відповідає формату :format.',
    'different'            => ':attribute та :other мусять відрізнятися.',
    'digits'               => ':attribute мусить містити :digits цифр.',
    'digits_between'       => ':attribute мусить містити від :min до :max цифр.',
    'dimensions'           => ':attribute має хибну ширину чи висоту.',
    'distinct'             => 'Поле :attribute містить дубль.',
    'email'                => ':attribute мусить бути чинною адресою е-пошти.',
    'exists'               => ':attribute — не ок.',
    'file'                 => ':attribute мусить бути файлом.',
    'filled'               => 'Полю :attribute бракує значення.',
    'image'                => ':attribute мусить бути зображенням.',
    'in'                   => ':attribute — не ок.',
    'in_array'             => 'Поля :attribute нема серед :other.',
    'integer'              => ':attribute мусить бути числом.',
    'ip'                   => ':attribute мусить бути чинною IP-адресою.',
    'ipv4'                 => ':attribute мусить бути чинною IPv4-адресою.',
    'ipv6'                 => ':attribute мусить бути чинною IPv6-адресою.',
    'json'                 => ':attribute мусить бути чинним JSON-рядком.',
    'max'                  => [
        'numeric' => ':attribute мусить не перевищувати :max.',
        'file'    => ':attribute мусить містити не більше, ніж :max кілобайтів.',
        'string'  => ':attribute мусить містити не більше, ніж :max символів.',
        'array'   => ':attribute мусить містити не більше, ніж :max пунктів.',
    ],
    'mimes'                => ':attribute мусить бути файлом: :values.',
    'mimetypes'            => ':attribute мусить бути файлом: :values.',
    'min'                  => [
        'numeric' => ':attribute мусить бути не менше, ніж :max.',
        'file'    => ':attribute мусить містити не менше, ніж :max кілобайтів.',
        'string'  => ':attribute мусить містити не менше, ніж :max символів.',
        'array'   => ':attribute мусить містити не менше, ніж :max пунктів.',
    ],
    'not_in'               => ':attribute містить хибний вибір.',
    'not_regex'            => 'Формат :attribute хибний.',
    'numeric'              => ':attribute мусить бути числом.',
    'present'              => 'Бракує поля :attribute.',
    'regex'                => 'Формат :attribute хибний.',
    'required'             => "Поле :attribute обов'язкове.",
    'required_if'          => "Поле :attribute обов'язкове, коли :other — :value.",
    'required_unless'      => "Поле :attribute обов'язкове unless :other is in :values.",
    'required_with'        => "Поле :attribute обов'язкове, коли є :values.",
    'required_with_all'    => "Поле :attribute обов'язкове, коли є :values.",
    'required_without'     => "Поле :attribute обов'язкове, коли бракує :values.",
    'required_without_all' => "Поле :attribute обов'язкове, коли бракує :values.",
    'same'                 => 'The :attribute and :other must match.',
    'size'                 => [
        'numeric' => ':attribute мусить бути :size.',
        'file'    => ':attribute мусить містити :size кілобайтів.',
        'string'  => ':attribute мусить містити :size символів.',
        'array'   => ':attribute мусить містити :size пунктів.',
    ],
    'string'               => ':attribute мусить бути рядком.',
    'timezone'             => ':attribute мусить бути чинним поясом.',
    'unique'               => ':attribute уже зайнято.',
    'uploaded'             => 'Не вдалося вивантажити :attribute.',
    'url'                  => 'Формат :attribute хибний.',

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
