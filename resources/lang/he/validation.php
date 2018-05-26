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

    'accepted'             => 'חובה לאשר את ה- :attribute.',
    'active_url'           => 'ה- :attribute לא URL תקין.',
    'after'                => 'ה- :attribute חייב להיות תאריך אחרי :date.',
    'after_or_equal'       => 'ה- :attribute חייב להיות תאריך אחרי או שווה ל- :date.',
    'alpha'                => 'ה- :attribute יכול לכלול רק אותיות.',
    'alpha_dash'           => 'ה- :attribute יכול לכלול רק אותיות, מספרים וקוים מפרידים.',
    'alpha_num'            => 'ה- :attribute יכול לכלול רק אותיות ומספרים.',
    'array'                => 'ה- :attribute חייב להיות מערך.',
    'before'               => 'ה- :attribute חייב להיות תאריך לפני :date.',
    'before_or_equal'      => 'ה- :attribute חייב להיות תאריך לפני או שווה ל- :date.',
    'between'              => [
        'numeric' => 'ה- :attribute צריך להיות בין :min ו- :max.',
        'file'    => 'ה- :attribute צריך להיות בין :min ו- :max קילובייט.',
        'string'  => 'ה- :attribute צריך להיות בין :min ו- :max אותיות.',
        'array'   => 'ה- :attribute צריך לכלול בין :min ו- :max פריטים.',
    ],
    'boolean'              => 'השדה :attribute חייב להיות אמת או שקר.',
    'confirmed'            => 'ה- :attribute אישור לא תואם.',
    'date'                 => 'ה- :attribute לא תאריך תקין.',
    'date_format'          => 'ה- :attribute לא תואם את הפורמט :format.',
    'different'            => 'ה- :attribute ו- :other חייבים להיות שונים.',
    'digits'               => 'ה- :attribute חייב להיות :digits ספרות.',
    'digits_between'       => 'ה- :attribute חייב להיות בין :min ו- :max ספרות.',
    'dimensions'           => 'ה- :attribute בעל ממדי תמונה לא תקינים.',
    'distinct'             => 'השדה :attribute בעל ערך כפול.',
    'email'                => 'ה- :attribute חייב להיות כתובת אימייל תקינה.',
    'exists'               => 'ה- :attribute הנבחר לא תקין.',
    'file'                 => 'ה- :attribute חייב להיות קובץ.',
    'filled'               => 'השדה :attribute חייב להיות בעל ערך.',
    'image'                => 'ה- :attribute חייב להיות תמונה.',
    'in'                   => 'ה- :attribute הנבחר לא תקין.',
    'in_array'             => 'השדה :attribute לא קיים ב- :other.',
    'integer'              => 'ה- :attribute צריך להיות מספר שלם.',
    'ip'                   => 'ה- :attribute צריך להיות כתובת IP תקינה.',
    'ipv4'                 => 'ה- :attribute צריך להיות כתובת IPv4 תקינה.',
    'ipv6'                 => 'ה- :attribute צריך להיות כתובת IPv6 תקינה.',
    'json'                 => 'ה- :attribute צריך להיות רצף אותיות JSON תקין.',
    'max'                  => [
        'numeric' => 'ה- :attribute צריך להיות מתחת ל- :max.',
        'file'    => 'ה- :attribute צריך להיות מתחת ל- :max קילובייט.',
        'string'  => 'ה- :attribute צריך להיות מתחת ל- :max תוים.',
        'array'   => 'ה- :attribute לא יכול לכלול יותר מ- :max פריטים.',
    ],
    'mimes'                => 'ה- :attribute צריך להיות קובץ מסוג: :values.',
    'mimetypes'            => 'ה- :attribute צריך להיות קובץ מסוג: :values.',
    'min'                  => [
        'numeric' => 'ה- :attribute צריך להיות לפחות :min.',
        'file'    => 'ה- :attribute צריך להיות לפחות :min קילובייט.',
        'string'  => 'ה- :attribute צריך להיות לפחות :min תוים.',
        'array'   => 'ה- :attribute צריך לכלול לפחות :min פריטים.',
    ],
    'not_in'               => 'ה- :attribute הנבחר אינו תקין.',
    'not_regex'            => 'הפורמט :attribute אינו תקין.',
    'numeric'              => 'ה- :attribute חייב להיות מספר.',
    'present'              => 'השדה :attribute חייב להיות קיים.',
    'regex'                => 'הפורמט :attribute לא תקין.',
    'required'             => 'השדה :attribute הכרחי.',
    'required_if'          => 'השדה :attribute הכרחי כש- :other הוא :value.',
    'required_unless'      => 'השדה :attribute הכרחי אלא אם :other בתוך :values.',
    'required_with'        => 'השדה :attribute הכרחי כש- :values קיים.',
    'required_with_all'    => 'השדה :attribute הכרחי כש- :values קיים.',
    'required_without'     => 'השדה :attribute הכרחי כש- :values לא קיים.',
    'required_without_all' => 'השדה :attribute הכרחי כשאף מן ה- :values לא נמצאים.',
    'same'                 => 'ה- :attribute ו- :other חייבים להתאים.',
    'size'                 => [
        'numeric' => 'ה- :attribute צריך להיות :size.',
        'file'    => 'ה- :attribute צריך להיות :size קילובייט.',
        'string'  => 'ה- :attribute צריך להיות :size תוים.',
        'array'   => 'ה- :attribute צריך לכלול :size פריטים.',
    ],
    'string'               => 'ה- :attribute חייב להיות רצף של אותיות.',
    'timezone'             => 'ה- :attribute חייב להיות איזור תקין.',
    'unique'               => 'ה- :attribute כבר נלקח.',
    'uploaded'             => 'ההעלאה של ה- :attribute נכשלה.',
    'url'                  => 'הפורמט של ה- :attribute אינו תקין.',

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
