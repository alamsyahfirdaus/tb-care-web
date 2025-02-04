<?php

namespace App\Helpers;

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
