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
    'accepted'             => ':attribute harus diterima.',
    'active_url'           => ':attribute bukan berupa URL yang benar.',
    'after'                => ':attribute harus berupa tanggal setelah :date.',
    'after_or_equal'       => ':attribute harus berupa tanggal yang sama atau setelah :date.',
    'alpha'                => ':attribute hanya boleh berisi huruf.',
    'alpha_dash'           => ':attribute hanya boleh berisi huruf, angka dan tanda minus.',
    'alpha_num'            => ':attribute hanya boleh berisi huruf dan angka.',
    'array'                => ':attribute harus berupa array.',
    'before'               => ':attribute harus berupa tanggal sebelum :date.',
    'before_or_equal'      => ':attribute harus berupa tanggal yang sama atau sebelum :date.',
    'between'              => [
        'numeric' => ':attribute harus antara :min dan :max.',
        'file'    => ':attribute harus antara :min dan :max KB.',
        'string'  => ':attribute harus antara :min dan :max karakter.',
        'array'   => ':attribute harus antara :min dan :max item.',
    ],
    'boolean'              => ':attribute harus berupa true atau false.',
    'confirmed'            => 'Konfirmasi :attribute tidak sama.',
    'date'                 => ':attribute bukan berupa tanggal yang benar.',
    'date_format'          => ':attribute tidak sesuai dengan format :format.',
    'different'            => ':attribute dan :other harus berbeda.',
    'digits'               => ':attribute harus berisi :digits digit.',
    'digits_between'       => ':attribute harus antara :min dan :max digit.',
    'dimensions'           => ':attribute berisi dimensi gambar yang tidak benar.',
    'distinct'             => 'Bagian :attribute memiliki duplikasi.',
    'email'                => ':attribute harus berupa alamat email yang benar.',
    'exists'               => ':attribute yang dipilih tidak benar.',
    'file'                 => ':attribute harus berupa berkas.',
    'filled'               => 'Bagian :attribute harus diisi.',
    'image'                => ':attribute harus berupa gambar.',
    'in'                   => ':attribute yang dipilih tidak benar.',
    'in_array'             => 'Bagian :attribute tidak ada dalam :other.',
    'integer'              => ':attribute harus berupa angka bulat.',
    'ip'                   => ':attribute harus berupa alamat IP yang benar.',
    'ipv4'                 => ':attribute harus berupa alamat IPv4 yang benar.',
    'ipv6'                 => ':attribute harus berupa alamat IPv6 yang benar.',
    'json'                 => ':attribute harus berupa string JSON yang benar.',
    'max'                  => [
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'file'    => ':attribute tidak boleh lebih dari :max kilobyte.',
        'string'  => ':attribute tidak boleh lebih dari :max karakter.',
        'array'   => ':attribute tidak boleh lebih dari :max item.',
    ],
    'mimes'                => ':attribute harus berupa berkas: :values.',
    'mimetypes'            => ':attribute harus berupa berkas: :values.',
    'min'                  => [
        'numeric' => ':attribute minimal harus :min.',
        'file'    => ':attribute minimal harus :min kilobyte.',
        'string'  => ':attribute minimal harus :min karakter.',
        'array'   => ':attribute minimal harus berisi :min item.',
    ],
    'not_in'               => ':attribute yang dipilih tidak benar.',
    'not_regex'            => 'Format :attribute tidak benar.',
    'numeric'              => ':attribute harus berupa angka.',
    'present'              => 'Bagian :attribute harus diisi.',
    'regex'                => 'Format :attribute tidak benar.',
    'required'             => 'Bagian :attribute harus diisi.',
    'required_if'          => 'Bagian :attribute harus diisi jika :other :value.',
    'required_unless'      => 'Bagian :attribute harus diisi kecuali jika :other :values.',
    'required_with'        => 'Bagian :attribute harus diisi jika ada :values.',
    'required_with_all'    => 'Bagian :attribute harus diisi jika ada :values.',
    'required_without'     => 'Bagian :attribute harus diisi jika tidak ada :values.',
    'required_without_all' => 'Bagian :attribute harus diisi jika tidak ada :values.',
    'same'                 => ':attribute dan :other harus sama.',
    'size'                 => [
        'numeric' => ':attribute harus :size.',
        'file'    => ':attribute harus berukuran :size kilobyte.',
        'string'  => ':attribute harus berisi :size karaker.',
        'array'   => ':attribute harus berisi :size item.',
    ],
    'string'               => ':attribute harus berupa string.',
    'timezone'             => ':attribute harus berupa zona yang benar.',
    'unique'               => ':attribute sudah digunakan.',
    'uploaded'             => ':attribute gagal diunggah.',
    'url'                  => 'Format :attribute tidak benar.',
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
