<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;

class EncryptedId
{
    /**
     * Encrypt an ID or Model into an encrypted string.
     *
     * @param int|string|Model|null $id
     * @return string
     */
    public static function encrypt($id): string
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        if (empty($id) && $id !== 0 && $id !== '0') {
            return '';
        }

        return Crypt::encryptString((string) $id);
    }

    /**
     * Decrypt an encrypted ID string into an integer ID.
     * Aborts with HTTP 404 if the value is invalid, empty, corrupted, or tampered.
     *
     * @param string|null $value
     * @return int
     */
    public static function decrypt($value): int
    {
        if (empty($value) || !is_string($value)) {
            abort(404, 'Data tidak ditemukan.');
        }

        try {
            $id = Crypt::decryptString($value);

            if (!ctype_digit((string) $id)) {
                abort(404, 'Data tidak ditemukan.');
            }

            return (int) $id;
        } catch (\Throwable $e) {
            abort(404, 'Data tidak ditemukan.');
        }
    }

    /**
     * Attempt to decrypt an encrypted ID without throwing 404.
     * Returns null if invalid or decryption fails.
     *
     * @param string|null $value
     * @return int|null
     */
    public static function tryDecrypt($value): ?int
    {
        if (empty($value) || !is_string($value)) {
            return null;
        }

        try {
            $id = Crypt::decryptString($value);

            if (!ctype_digit((string) $id)) {
                return null;
            }

            return (int) $id;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Check if a value is a valid encrypted ID.
     *
     * @param string|null $value
     * @return bool
     */
    public static function isValid($value): bool
    {
        return static::tryDecrypt($value) !== null;
    }
}
