<?php

namespace App\Helpers;

class DateHelper
{
    public static function convertDate($date)
    {
        $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                   'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $dateParts = explode('-', $date);
        $year = $dateParts[0];
        $month = (int) $dateParts[1];
        $day = $dateParts[2];

        return $day . ' ' . $months[$month] . ' ' . $year;
    }
}
