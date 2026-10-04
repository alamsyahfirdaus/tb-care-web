<?php

namespace App\Helpers {

    class DateHelper
    {
        public static function convertDate($date)
        {
            if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return null;
            }

            $months = [
                1 => 'Januari',
                'Februari',
                'Maret',
                'April',
                'Mei',
                'Juni',
                'Juli',
                'Agustus',
                'September',
                'Oktober',
                'November',
                'Desember'
            ];

            $dateParts = explode('-', $date);

            if (count($dateParts) !== 3) {
                return null;
            }

            $year = $dateParts[0];
            $month = (int) $dateParts[1];
            $day = $dateParts[2];

            return $day . ' ' . $months[$month] . ' ' . $year;
        }
    }
}

namespace {

    use App\Support\EncryptedId;

    if (!function_exists('encrypt_id')) {
        /**
         * Encrypt an ID or Model for browser-safe URLs and forms.
         *
         * @param int|string|\Illuminate\Database\Eloquent\Model|null $id
         * @return string
         */
        function encrypt_id($id): string
        {
            return EncryptedId::encrypt($id);
        }
    }

    if (!function_exists('decrypt_id')) {
        /**
         * Decrypt an encrypted ID string into an integer ID.
         * Aborts with HTTP 404 if invalid.
         *
         * @param string|null $value
         * @return int
         */
        function decrypt_id($value): int
        {
            return EncryptedId::decrypt($value);
        }
    }
}
