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

    'accepted'             => ':attribute muss akzeptiert sein .',
    'active_url'           => ':attribute ist keine g&uuml;ltige URL.',
    'after'                => ':attribute muss ein Datum nach dem :date sein.',
    'after_or_equal'       => ':attribute muss auf den :date oder danach fallen.',
    'alpha'                => ':attribute darf nur Buchstaben beinhalten.',
    'alpha_dash'           => ':attribute darf nur Buchstaben, Zahlen, und Bindestriche beinhalten.',
    'alpha_num'            => ':attribute darf nur Buchstaben und Zahlen beinhalten.',
    'array'                => ':attribute muss ein Array sein.',
    'before'               => ':attribute muss ein Datum vor dem :date sein.',
    'before_or_equal'      => ':attribute muss auf den :date oder davor fallen.',
    'between'              => [
        'numeric' => ':attribute muss eine Zahl zwischen :min bis :max sein.',
        'file'    => ':attribute muss zwischen :min bis :max Kilobytes haben.',
        'string'  => ':attribute muss zwischen :min bis :max Zeichen lang sein.',
        'array'   => ':attribute muss zwischen :min bis :max Objekte beinhalten.',
    ],
    'boolean'              => ':attribute muss "true" oder "false" sein.',
    'confirmed'            => ':attribute-Best&auml;tigung stimmt nicht &uuml;berein.',
    'date'                 => ':attribute ist kein g&uuml;ltiges Datum.',
    'date_format'          => ':attribute passt nicht in das :format Format.',
    'different'            => ':attribute und :other d&uuml;rfen nicht gleich sein.',
    'digits'               => ':attribute muss genau :digits Ziffern haben.',
    'digits_between'       => ':attribute muss :min bis :max Ziffern haben.',
    'dimensions'           => ':attribute hat ung&uuml;ltige Bildma&szlig;e.',
    'distinct'             => ':attribute-Feld hat einen doppelten Wert.',
    'email'                => ':attribute muss eine g&uuml;ltige E-Mail-Adresse sein.',
    'exists'               => 'Gew&auml;hlter :attribute-Wert ist ung&uuml;ltig.',
    'file'                 => ':attribute muss eine Datei sein.',
    'filled'               => ':attribute muss ausgef&uuml;llt sein.',
    'image'                => ':attribute muss ein Bild sein.',
    'in'                   => 'Gew&auml;hlter :attribute-Wert ist ung&uuml;ltig.',
    'in_array'             => ':attribute-Feld existiert nicht in :other.',
    'integer'              => ':attribute muss eine Zahl sein.',
    'ip'                   => ':attribute muss eine g&uuml;ltige IP-Adresse sein.',
    'ipv4'                 => ':attribute muss eine g&uuml;ltige IPv4-Adresse sein.',
    'ipv6'                 => ':attribute muss eine g&uuml;ltige IPv6-Adresse sein.',
    'json'                 => ':attribute muss eine g&uuml;ltige JSON-Zeichenfolge sein.',
    'max'                  => [
        'numeric' => ':attribute darf nicht gr&omul;&szlig;er als :max sein.',
        'file'    => ':attribute darf nicht gr&omul;&szlig;er als :max Kilobytes sein.',
        'string'  => ':attribute darf nicht mehr als :max Zeichen haben.',
        'array'   => ':attribute darf nicht mehr als :max Objekte beinhalten.',
    ],
    'mimes'                => ':attribute muss eine Datei einer dieser Typen sein: :values.',
    'mimetypes'            => ':attribute muss eine Datei einer dieser Typen sein: :values.',
    'min'                  => [
        'numeric' => ':attribute muss gr&omul;&szlig;er als oder gleich :min sein.',
        'file'    => ':attribute muss mindestens :min Kilobytes gro&szlig; sein.',
        'string'  => ':attribute muss mindestens :min Zeichen haben.',
        'array'   => ':attribute muss mindestens :min Objekte beinhalten.',
    ],
    'not_in'               => 'Gew&auml;hlter :attribute-Wert ist ung&uuml;ltig.',
    'not_regex'            => ':attribute-Format ist ung&uuml;ltig.',
    'numeric'              => ':attribute muss eine Zahl sein.',
    'present'              => ':attribute muss vorhanden sein.',
    'regex'                => ':attribute-Format ist ung&uuml;ltig.',
    'required'             => 'Das :attribute-Feld ist ben&ouml;tigt.',
    'required_if'          => 'Das :attribute-Feld ist ben&ouml;tigt wenn :other :value ist.',
    'required_unless'      => 'Das :attribute-Feld ist ben&ouml;tigt es sei denn :other ist in :values.',
    'required_with'        => 'Das :attribute-Feld ist ben&ouml;tigt wenn :values vorhanden ist.',
    'required_with_all'    => 'Das :attribute-Feld ist ben&ouml;tigt wenn :values vorhanden ist.',
    'required_without'     => 'Das :attribute-Feld ist ben&ouml;tigt wenn :values nicht vorhanden ist.',
    'required_without_all' => 'Das :attribute-Feld ist ben&ouml;tigt wenn keiner von :values vorhanden ist.',
    'same'                 => ':attribute und :other m&uuml;ssen gleich sein.',
    'size'                 => [
        'numeric' => ':attribute muss :size sein.',
        'file'    => ':attribute muss :size Kilobytes gro&szlig; sein.',
        'string'  => ':attribute muss :size Zeichen lang sein.',
        'array'   => ':attribute muss :size Objekte beinhalten.',
    ],
    'string'               => ':attribute muss eine Zeichenkette sein.',
    'timezone'             => ':attribute muss eine g&uml;ltige Zeitzone sein.',
    'unique'               => ':attribute ist bereits in Verwendung.',
    'uploaded'             => ':attribute hochladen ist fehlgeschlagen.',
    'url'                  => ':attribute-Format ist ung&uml;lig.',

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
