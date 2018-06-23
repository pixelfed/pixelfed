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

    'accepted'             => 'Πρέπει να αποδεκτείτε το :attribute.',
    'active_url'           => 'Το :attribute δεν είναι έγκυρο URL.',
    'after'                => 'Η :attribute πρέπει να είναι μετά την :date.',
    'after_or_equal'       => 'Η :attribute πρέπει να είναι ακριβώς :date ή αργότερα.',
    'alpha'                => 'Το στοιχείο :attribute δέχεται μόνο γράμματα.',
    'alpha_dash'           => 'Το στοιχείο :attribute δέχεται μόνο γράμματα, αριθμούς και παύλες.',
    'alpha_num'            => 'Το στοιχείο :attribute δέχεται μόνο γράμματα και αριθμούς.',
    'array'                => 'Το στοιχείο :attribute πρέπει να είναι πίνακας.',
    'before'               => 'Το στοιχείο :attribute πρέπει να είναι πριν την :date.',
    'before_or_equal'      => 'Το στοιχείο :attribute πρέπει να είναι ακριβώς :date ή νωρίτερα.',
    'between'              => [
        'numeric' => 'Το στοιχείο :attribute πρέπει να είναι μεταξύ :min και :max.',
        'file'    => 'Το στοιχείο :attribute πρέπει να είναι μεταξύ :min και :max kb.',
        'string'  => 'Το στοιχείο :attribute πρέπει να είναι μεταξύ :min και :max χαρακτήρες.',
        'array'   => 'Το στοιχείο :attribute πρέπει να έχει μεταξύ :min και :max αντικείμενα.',
    ],
    'boolean'              => 'Το στοιχείο :attribute πρέπει να είναι είτε αληθές είτε ψευδές.',
    'confirmed'            => 'Η επιβεβαίωση του στοιχείου :attribute δεν ταιριάζει.',
    'date'                 => 'Το στοιχείο :attribute δεν είναι έγκυρη ημερομηνία.',
    'date_format'          => 'Το στοιχείο :attribute δεν έχει τη σωστή μορφοποίηση: :format.',
    'different'            => 'Τα στοιχεία :attribute και :other πρέπει να διαφέρουν.',
    'digits'               => 'Το στοιχείο :attribute πρέπει να αποτελείται από :digits ψηφία.',
    'digits_between'       => 'Το στοιχείο :attribute πρέπει να έχει μεταξύ :min και :max ψηφία.',
    'dimensions'           => 'Το στοιχείο :attribute δεν έχει έγκυρες διαστάσεις εικόνας.',
    'distinct'             => 'Το πεδίο :attribute έχει διπλή τιμή.',
    'email'                => 'Το :attribute πρέπει να είναι έγκυρη διεύθυνση e-mail.',
    'exists'               => 'Το επιλεγμένο στοιχείο :attribute δεν είναι έγκυρο.',
    'file'                 => 'Το στοιχείο :attribute πρέπει να είναι αρχείο.',
    'filled'               => 'Το πεδίο :attribute πρέπει να έχει τιμή.',
    'image'                => 'Το στοιχείο :attribute πρέπει να είναι εικόνα.',
    'in'                   => 'Το επιλεγμένο στοιχείο :attribute δεν είναι έγκυρο.',
    'in_array'             => 'Το πεδίο :attribute δεν υπάρχει στο :other.',
    'integer'              => 'Το στοιχείο :attribute πρέπει να είναι ακέραιος.',
    'ip'                   => 'Το στοιχείο :attribute πρέπει να είναι έγκυρη διεύθυνση IP.',
    'ipv4'                 => 'Το στοιχείο :attribute πρέπει να είναι έγκυρη διεύθυνση IPv4.',
    'ipv6'                 => 'Το στοιχείο :attribute πρέπει να είναι έγκυρη διεύθυνση IPv6.',
    'json'                 => 'Το στοιχείο :attribute πρέπει να είναι έγκυρη συμβολοσειρά JSON.',
    'max'                  => [
        'numeric' => 'Το στοιχείο :attribute δεν μπορεί να είναι μεγαλύτερο από :max.',
        'file'    => 'Το στοιχείο :attribute δεν μπορεί να είναι μεγαλύτερο από :max kb.',
        'string'  => 'Το στοιχείο :attribute δεν μπορεί να είναι μεγαλύτερο από :max χαρακτήρες.',
        'array'   => 'Το στοιχείο :attribute may not have more than :max items.',
    ],
    'mimes'                => 'Το στοιχείο :attribute πρεπει να είναι αρχείο τύπου: :values.',
    'mimetypes'            => 'Το στοιχείο :attribute πρεπει να είναι αρχείο τύπου: :values.',
    'min'                  => [
        'numeric' => 'Το στοιχείο :attribute πρέπει να είναι τουλάχιστον :min.',
        'file'    => 'Το στοιχείο :attribute πρέπει να είναι τουλάχιστον :min kb.',
        'string'  => 'Το στοιχείο :attribute πρέπει να είναι τουλάχιστον :min χαρακτήρες.',
        'array'   => 'Το στοιχείο :attribute must have at least :min items.',
    ],
    'not_in'               => 'Το επιλεγμένο στοιχείο :attribute δεν είναι έγκυρο.',
    'not_regex'            => 'Η μορφοποίηση του στοιχείου :attribute δεν είναι έγκυρη.',
    'numeric'              => 'Το στοιχείο :attribute πρέπει να είναι αριθμός.',
    'present'              => 'Το πεδίο :attribute πρέπει να υπάρχει.',
    'regex'                => 'Η μορφοποίηση του στοιχείου :attribute δεν είναι έγκυρη.',
    'required'             => 'Το πεδίο :attribute είναι απαραίτητο.',
    'required_if'          => 'Το πεδίο :attribute είναι απαραίτητο όταν το :other είναι :value.',
    'required_unless'      => 'Το πεδίο :attribute είναι απαραίτητο unless :other είναι ένα από: :values.',
    'required_with'        => 'Το πεδίο :attribute είναι απαραίτητο όταν το :values υπάρχει.',
    'required_with_all'    => 'Το πεδίο :attribute είναι απαραίτητο όταν το :values υπάρχει.',
    'required_without'     => 'Το πεδίο :attribute είναι απαραίτητο όταν το :values δεν υπάρχει.',
    'required_without_all' => 'Το πεδίο :attribute είναι απαραίτητο όταν καμία από τις τιμές :values δεν υπάρχει.',
    'same'                 => 'Τα στοιχεία :attribute και :other πρέπει να ταιριάζουν.',
    'size'                 => [
        'numeric' => 'Το στοιχείο :attribute πρέπει να έχει μέγεθος :size.',
        'file'    => 'Το στοιχείο :attribute πρέπει να έχει μέγεθος :size kb.',
        'string'  => 'Το στοιχείο :attribute πρέπει να έχει μέγεθος :size χαρακτήρες.',
        'array'   => 'Το στοιχείο :attribute πρέπει να έχει :size αντικείμενα.',
    ],
    'string'               => 'Το στοιχείο :attribute πρέπει να είναι συμβολοσειρά.',
    'timezone'             => 'Το στοιχείο :attribute πρέπει να είναι έγκυρη ζώνη ώρας.',
    'unique'               => 'Το στοιχείο :attribute είναι πιασμένο :-(.',
    'uploaded'             => 'Το στοιχείο :attribute απέτυχε να ανέβει.',
    'url'                  => 'Η μορφοποίηση του στοιχείου :attribute δεν είναι έγκυρη.',

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
