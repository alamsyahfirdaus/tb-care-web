<?php

$attr = ":" . ucfirst('attribute');
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

    
    'accepted' => $attr . ' harus diterima.',
    'accepted_if' => $attr . ' harus diterima ketika :other adalah :value.',
    'active_url' => $attr . ' bukan URL yang valid.',
    'after' => $attr . ' harus tanggal setelah :date.',
    'after_or_equal' => $attr . ' harus tanggal setelah atau sama dengan :date.',
    'alpha' => $attr . ' hanya boleh berisi huruf.',
    'alpha_dash' => $attr . ' hanya boleh berisi huruf, angka, dash, dan garis bawah.',
    'alpha_num' => $attr . ' hanya boleh berisi huruf dan angka.',
    'array' => $attr . ' harus berupa array.',
    'before' => $attr . ' harus tanggal sebelum :date.',
    'before_or_equal' => $attr . ' tidak boleh setelah hari ini.',
    'between' => [
        'numeric' => $attr . ' harus antara :min dan :max.',
        'file' => $attr . ' harus antara :min dan :max kilobita.',
        'string' => $attr . ' harus antara :min dan :max karakter.',
        'array' => $attr . ' harus memiliki antara :min dan :max item.',
    ],
    'boolean' => 'Bidang :attribute harus benar atau salah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => $attr . ' salah.',
    'date' => $attr . ' bukan tanggal yang valid.',
    'date_equals' => $attr . ' harus tanggal yang sama dengan :date.',
    'date_format' => $attr . ' tidak cocok dengan format :format.',
    'declined' => $attr . ' harus ditolak.',
    'declined_if' => $attr . ' harus ditolak ketika :other adalah :value.',
    'different' => $attr . ' dan :other harus berbeda.',
    'digits' => $attr . ' harus :digits digit.',
    'digits_between' => $attr . ' harus antara :min dan :max digit.',
    'dimensions' => $attr . ' memiliki dimensi gambar yang tidak valid.',
    'distinct' => 'Bidang :attribute memiliki nilai duplikat.',
    'email' => $attr . ' harus alamat email yang valid.',
    'ends_with' => $attr . ' harus diakhiri dengan salah satu dari: :values.',
    'enum' => $attr . ' yang dipilih tidak valid.',
    'exists' => $attr . ' yang dipilih tidak valid.',
    'file' => $attr . ' harus berupa file.',
    'filled' => 'Bidang :attribute harus memiliki nilai.',
    'gt' => [
        'numeric' => $attr . ' harus lebih besar dari :value.',
        'file' => $attr . ' harus lebih besar dari :value kilobita.',
        'string' => $attr . ' harus lebih besar dari :value karakter.',
        'array' => $attr . ' harus memiliki lebih dari :value item.',
    ],
    'gte' => [
        'numeric' => $attr . ' harus lebih besar dari atau sama dengan :value.',
        'file' => $attr . ' harus lebih besar dari atau sama dengan :value kilobita.',
        'string' => $attr . ' harus lebih besar dari atau sama dengan :value karakter.',
        'array' => $attr . ' harus memiliki :value item atau lebih.',
    ],
    'image' => $attr . ' harus berupa gambar.',
    'in' => $attr . ' yang dipilih tidak valid.',
    'in_array' => 'Bidang :attribute tidak ada dalam :other.',
    'integer' => $attr . ' harus berupa bilangan bulat.',
    'ip' => $attr . ' harus alamat IP yang valid.',
    'ipv4' => $attr . ' harus alamat IPv4 yang valid.',
    'ipv6' => $attr . ' harus alamat IPv6 yang valid.',
    'json' => $attr . ' harus berupa string JSON yang valid.',
    'lt' => [
        'numeric' => $attr . ' harus kurang dari :value.',
        'file' => $attr . ' harus kurang dari :value kilobita.',
        'string' => $attr . ' harus kurang dari :value karakter.',
        'array' => $attr . ' harus memiliki kurang dari :value item.',
    ],
    'lte' => [
        'numeric' => $attr . ' harus kurang dari atau sama dengan :value.',
        'file' => $attr . ' harus kurang dari atau sama dengan :value kilobita.',
        'string' => $attr . ' harus kurang dari atau sama dengan :value karakter.',
        'array' => $attr . ' tidak boleh memiliki lebih dari :value item.',
    ],
    'mac_address' => $attr . ' harus alamat MAC yang valid.',
    'max' => [
        'numeric' => $attr . ' tidak boleh lebih besar dari :max.',
        'file' => $attr . ' tidak boleh lebih besar dari :max kilobita.',
        'string' => $attr . ' tidak boleh lebih besar dari :max karakter.',
        'array' => $attr . ' tidak boleh memiliki lebih dari :max item.',
    ],
    'mimes' => $attr . ' harus berupa file dengan tipe: :values.',
    'mimetypes' => $attr . ' harus berupa file dengan tipe: :values.',
    'min' => [
        'numeric' => $attr . ' harus minimal :min.',
        'file' => $attr . ' harus minimal :min kilobita.',
        'string' => $attr . ' harus minimal :min karakter.',
        'array' => $attr . ' harus memiliki minimal :min item.',
    ],
    'multiple_of' => $attr . ' harus merupakan kelipatan dari :value.',
    'not_in' => $attr . ' yang dipilih tidak valid.',
    'not_regex' => 'Format :attribute tidak valid.',
    'numeric' => $attr . ' harus berupa angka.',
    'password' => $attr . ' salah.',
    'present' => 'Bidang :attribute harus ada.',
    'prohibited' => 'Bidang :attribute dilarang.',
    'prohibited_if' => 'Bidang :attribute dilarang ketika :other adalah :value.',
    'prohibited_unless' => 'Bidang :attribute dilarang kecuali :other ada dalam :values.',
    'prohibits' => 'Bidang :attribute melarang :other untuk hadir.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => 'Bidang :attribute wajib diisi.',
    'required_array_keys' => 'Bidang :attribute harus berisi entri untuk: :values.',
    'required_if' => 'Bidang :attribute diperlukan ketika :other adalah :value.',
    'required_unless' => 'Bidang :attribute diperlukan kecuali :other ada dalam :values.',
    'required_with' => 'Bidang :attribute diperlukan ketika :values ada.',
    'required_with_all' => 'Bidang :attribute diperlukan ketika :values ada.',
    'required_without' => 'Bidang :attribute diperlukan ketika :values tidak ada.',
    'required_without_all' => 'Bidang :attribute diperlukan ketika tidak ada satu pun dari :values ada.',
    'same' => $attr . ' dan :other harus cocok.',
    'size' => [
        'numeric' => $attr . ' harus :size.',
        'file' => $attr . ' harus :size kilobita.',
        'string' => $attr . ' harus :size karakter.',
        'array' => $attr . ' harus berisi :size item.',
    ],
    'starts_with' => $attr . ' harus dimulai dengan salah satu dari: :values.',
    'string' => $attr . ' harus berupa string.',
    'timezone' => $attr . ' harus zona waktu yang valid.',
    'unique' => $attr . ' sudah ada.',
    'uploaded' => $attr . ' gagal diunggah.',
    'url' => $attr . ' harus URL yang valid.',
    'uuid' => $attr . ' harus UUID yang valid.',

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
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => 'nama',
        'user_type_id' => 'level',
        'subdistrict_id' => 'kecamatan',
        'telephone' => 'telepon',
        'gender' => 'jenis kelamin',
        'place_of_birth' => 'tempat lahir',
        'date_of_birth' => 'tanggal lahir',
        'office_type_id' => 'dinas kesehatan',
        'office_address' => 'alamat kantor',
        'puskesmas_id' => 'puskesmas',
        'diagnosis_date' => 'tanggal diagnosa',
        'address' => 'alamat',
        'occupation' => 'pekerjaan',
        'height' => 'tinggi badan',
        'weight' => 'berat badan',
        'blood_type' => 'golongan darah',
        'district_id' => 'kapubaten/kota',
    ],

];
