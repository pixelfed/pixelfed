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
    'accepted'             => ':attribute doit être accepté.',
    'active_url'           => ':attribute n'est pas un Lien valide.',
    'after'                => ':attribute doit être une date après :date.',
    'after_or_equal'       => ':attribute doit être une date après ou égale à :date.',
    'alpha'                => ':attribute peut contenir uniquement des lettres.',
    'alpha_dash'           => ':attribute peut contenir uniquement des lettres, des chiffres et des tirets.',
    'alpha_num'            => ':attribute peut contenir uniquement des lettres et des chiffres.',
    'array'                => ':attribute doit être un tableau.',
    'before'               => ':attribute doit être une date avant :date.',
    'before_or_equal'      => ':attribute doit être une date avant ou égale à :date.',
    'between'              => [
        'numeric' => ':attribute doit être compris entre :min et :max.',
        'file'    => ':attribute doit être compris entre :min et :max Kilo-octets.',
        'string'  => ':attribute doit être compris entre :min et :max Caractères.',
        'array'   => ':attribute doit avoir entre :min et :max Articles.',
    ],
    'boolean'              => ':attribute le champ doit être vrai ou faux.',
    'confirmed'            => ':attribute la confirmation ne correspond pas.',
    'date'                 => ':attribute n'est pas une date valide.',
    'date_format'          => ':attribute ne correspond pas au format :format.',
    'different'            => ':attribute et :other doit être différent.',
    'digits'               => ':attribute doit être :digits chiffres.',
    'digits_between'       => ':attribute doit être compris entre :min et :max chiffres.',
    'dimensions'           => ':attribute a des dimensions d'image non valides.',
    'distinct'             => ':attribute le champ a une valeur dupliquée.',
    'email'                => ':attribute doit être une adresse e-mail valide.',
    'exists'               => ':attribute sélectionné n'est pas valide.',
    'file'                 => ':attribute doit être un fichier.',
    'filled'               => ':attribute le champ doit avoir une valeur.',
    'image'                => ':attribute doit être une image.',
    'in'                   => ':attribute sélectionner n'est pas valide.',
    'in_array'             => ':attribute le champ n'existe pas dans :other.',
    'integer'              => ':attribute doit être un entier.',
    'ip'                   => ':attribute doit être une adresse IP valide.',
    'ipv4'                 => ':attribute doit être une adresse IPv4 valide.',
    'ipv6'                 => ':attribute doit être une adresse IPv6 valide.',
    'json'                 => ':attribute doit être une chaîne JSON valide.',
    'max'                  => [
        'numeric' => ':attribute peut ne pas être supérieur à :max.',
        'file'    => ':attribute peut ne pas être supérieur :max Kilo-octets.',
        'string'  => ':attribute peut ne pas être supérieur :max Caractères.',
        'array'   => ':attribute peut ne pas être supérieur :max Articles.',
    ],
    'mimes'                => ':attribute doit être un fichier de type: :values.',
    'mimetypes'            => ':attribute doit être un fichier de type: :values.',
    'min'                  => [
        'numeric' => ':attribute doit être au moins :min.',
        'file'    => ':attribute doit être au moins :min Kilo-octets.',
        'string'  => ':attribute doit être au moins :min Caractères.',
        'array'   => ':attribute doit avoir au moins :min Articles.',
    ],
    'not_in'               => ':attribute sélectionner n'est pas valide.',
    'not_regex'            => ':attribute le format n'est pas valide.',
    'numeric'              => ':attribute doit être un nombre.',
    'present'              => ':attribute le champ doit être présent.',
    'regex'                => ':attribute le format n'est pas valide.',
    'required'             => ':attribute champ requis.',
    'required_if'          => ':attribute champ requis lorsque :other est :value.',
    'required_unless'      => ':attribute champ obligatoire à moins que :other est en :values.',
    'required_with'        => ':attribute champ requis lorsque :values est présent.',
    'required_with_all'    => ':attribute champ requis lorsque :values est présent.',
    'required_without'     => ':attribute champ requis lorsque :values est non présente.',
    'required_without_all' => ':attribute champ requis lorsqu'aucun des :values sont présents.',
    'same'                 => ':attribute et :other doit correspondre.',
    'size'                 => [
        'numeric' => ':attribute doit être :size.',
        'file'    => ':attribute doit être :size Kilo-octets.',
        'string'  => ':attribute doit être :size Caractères.',
        'array'   => ':attribute doit contenir :size Articles.',
    ],
    'string'               => ':attribute doit être une chaîne.',
    'timezone'             => ':attribute doit être une zone valide.',
    'unique'               => ':attribute a déjà été prise.',
    'uploaded'             => ':attribute Impossible de télécharger.',
    'url'                  => ':attribute le format n'est pas valide.',
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
