<?php

namespace App\Helpers;

class NameFormatter
{
    /**
     * Formats a person name to Title Case while preserving hyphens and apostrophes,
     * removing excess whitespace.
     *
     * Example:
     * - "anes Maulida sukmara" -> "Anes Maulida Sukmara"
     * - "ALAMSYAH FIRDAUS" -> "Alamsyah Firdaus"
     * - "dewi-ayu lestari" -> "Dewi-Ayu Lestari"
     * - "d'angelo" -> "D'Angelo"
     * - "sa'diyah" -> "Sa'diyah"
     * - "  dede   sumiati  " -> "Dede Sumiati"
     *
     * @param string|null $name
     * @return string
     */
    public static function format(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        $trimmed = trim(preg_replace('/\s+/u', ' ', $name));
        if ($trimmed === '') {
            return '';
        }

        // Split by single space
        $words = explode(' ', $trimmed);
        $formattedWords = array_map(function ($word) {
            // Check hyphens inside words (e.g., Dewi-Ayu)
            if (str_contains($word, '-')) {
                $subParts = explode('-', $word);
                $formattedSub = array_map(function ($part) {
                    return self::formatSingleWord($part);
                }, $subParts);
                return implode('-', $formattedSub);
            }

            return self::formatSingleWord($word);
        }, $words);

        return implode(' ', $formattedWords);
    }

    /**
     * Formats a single word or token, honoring apostrophes if present.
     */
    private static function formatSingleWord(string $word): string
    {
        if ($word === '') {
            return '';
        }

        // Handle apostrophes (e.g. Sa'diyah, Ma'ruf vs D'Angelo, O'Connor)
        if (str_contains($word, "'")) {
            $parts = explode("'", $word);
            $formattedParts = [];
            foreach ($parts as $idx => $p) {
                if ($p === '') {
                    $formattedParts[] = '';
                    continue;
                }
                if ($idx === 0) {
                    $formattedParts[] = mb_convert_case(mb_substr($p, 0, 1, 'UTF-8'), MB_CASE_UPPER, 'UTF-8') .
                                        mb_convert_case(mb_substr($p, 1, null, 'UTF-8'), MB_CASE_LOWER, 'UTF-8');
                } else {
                    $prev = $parts[$idx - 1] ?? '';
                    if (mb_strlen($prev, 'UTF-8') === 1) {
                        // Western patronymic prefix e.g. D'Angelo, O'Connor
                        $formattedParts[] = mb_convert_case(mb_substr($p, 0, 1, 'UTF-8'), MB_CASE_UPPER, 'UTF-8') .
                                            mb_convert_case(mb_substr($p, 1, null, 'UTF-8'), MB_CASE_LOWER, 'UTF-8');
                    } else {
                        // Indonesian / Arabic glottal stop e.g. Sa'diyah, Ma'ruf, Syafi'i
                        $formattedParts[] = mb_convert_case($p, MB_CASE_LOWER, 'UTF-8');
                    }
                }
            }
            return implode("'", $formattedParts);
        }

        return mb_convert_case(mb_substr($word, 0, 1, 'UTF-8'), MB_CASE_UPPER, 'UTF-8') .
               mb_convert_case(mb_substr($word, 1, null, 'UTF-8'), MB_CASE_LOWER, 'UTF-8');
    }
}
