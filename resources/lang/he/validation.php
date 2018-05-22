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

    'accepted'             => 'ה- :attribute must be accepted.',
    'active_url'           => 'ה- :attribute is not a valid URL.',
    'after'                => 'ה- :attribute must be a date after :date.',
    'after_or_equal'       => 'ה- :attribute must be a date after or equal to :date.',
    'alpha'                => 'ה- :attribute may only contain letters.',
    'alpha_dash'           => 'ה- :attribute may only contain letters, numbers, and dashes.',
    'alpha_num'            => 'ה- :attribute may only contain letters and numbers.',
    'array'                => 'ה- :attribute must be an array.',
    'before'               => 'ה- :attribute must be a date before :date.',
    'before_or_equal'      => 'ה- :attribute must be a date before or equal to :date.',
    'between'              => [
        'numeric' => 'ה- :attribute צריך להיות בין :min and :max.',
        'file'    => 'ה- :attribute צריך להיות בין :min and :max kilobytes.',
        'string'  => 'ה- :attribute צריך להיות בין :min and :max characters.',
        'array'   => 'ה- :attribute must have between :min and :max items.',
    ],
    'boolean'              => 'ה- :attribute field must be true or false.',
    'confirmed'            => 'ה- :attribute confirmation does not match.',
    'date'                 => 'ה- :attribute is not a valid date.',
    'date_format'          => 'ה- :attribute does not match the format :format.',
    'different'            => 'ה- :attribute and :other must be different.',
    'digits'               => 'ה- :attribute must be :digits digits.',
    'digits_between'       => 'ה- :attribute must be between :min and :max digits.',
    'dimensions'           => 'ה- :attribute has invalid image dimensions.',
    'distinct'             => 'ה- :attribute field has a duplicate value.',
    'email'                => 'ה- :attribute must be a valid email address.',
    'exists'               => 'ה- selected :attribute is invalid.',
    'file'                 => 'ה- :attribute must be a file.',
    'filled'               => 'ה- :attribute field must have a value.',
    'image'                => 'ה- :attribute must be an image.',
    'in'                   => 'ה- selected :attribute is invalid.',
    'in_array'             => 'ה- :attribute field does not exist in :other.',
    'integer'              => 'ה- :attribute must be an integer.',
    'ip'                   => 'ה- :attribute must be a valid IP address.',
    'ipv4'                 => 'ה- :attribute must be a valid IPv4 address.',
    'ipv6'                 => 'ה- :attribute must be a valid IPv6 address.',
    'json'                 => 'ה- :attribute must be a valid JSON string.',
    'max'                  => [
        'numeric' => 'ה- :attribute may not be greater than :max.',
        'file'    => 'ה- :attribute may not be greater than :max kilobytes.',
        'string'  => 'ה- :attribute may not be greater than :max characters.',
        'array'   => 'ה- :attribute may not have more than :max items.',
    ],
    'mimes'                => 'ה- :attribute צריך להיות קובץ מסוג: :values.',
    'mimetypes'            => 'ה- :attribute צריך להיות קובץ מסוג: :values.',
    'min'                  => [
        'numeric' => 'ה- :attribute צריך להיות לפחות :min.',
        'file'    => 'ה- :attribute צריך להיות לפחות :min kilobytes.',
        'string'  => 'ה- :attribute צריך להיות לפחות :min characters.',
        'array'   => 'ה- :attribute must have at least :min items.',
    ],
    'not_in'               => 'ה- selected :attribute is invalid.',
    'not_regex'            => 'ה- :attribute format is invalid.',
    'numeric'              => 'ה- :attribute must be a number.',
    'present'              => 'ה- :attribute field must be present.',
    'regex'                => 'ה- :attribute format is invalid.',
    'required'             => 'ה- :attribute field is required.',
    'required_if'          => 'ה- :attribute field is required when :other is :value.',
    'required_unless'      => 'ה- :attribute field is required unless :other is in :values.',
    'required_with'        => 'ה- :attribute field is required when :values is present.',
    'required_with_all'    => 'ה- :attribute field is required when :values is present.',
    'required_without'     => 'ה- :attribute field is required when :values is not present.',
    'required_without_all' => 'ה- :attribute field is required when none of :values are present.',
    'same'                 => 'ה- :attribute and :other must match.',
    'size'                 => [
        'numeric' => 'ה- :attribute must be :size.',
        'file'    => 'ה- :attribute must be :size kilobytes.',
        'string'  => 'ה- :attribute must be :size characters.',
        'array'   => 'ה- :attribute must contain :size items.',
    ],
    'string'               => 'ה- :attribute must be a string.',
    'timezone'             => 'ה- :attribute must be a valid zone.',
    'unique'               => 'ה- :attribute has already been taken.',
    'uploaded'             => 'ה- :attribute failed to upload.',
    'url'                  => 'ה- :attribute format is invalid.',

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
