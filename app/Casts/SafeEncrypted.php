<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class SafeEncrypted implements CastsAttributes
{
    /**
     * Cast the given encrypted storage string to plain text for application use.
     * Gracefully falls back to plain text if legacy record was created before encryption.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // Legacy unencrypted plain text fallback
            return $value;
        } catch (\Throwable $e) {
            return $value;
        }
    }

    /**
     * Prepare the raw plain text value for encrypted storage at rest (AES-256-CBC).
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // Avoid double-encrypting already encrypted ciphertext
        try {
            Crypt::decryptString($value);
            return $value;
        } catch (DecryptException $e) {
            return Crypt::encryptString((string) $value);
        } catch (\Throwable $e) {
            return Crypt::encryptString((string) $value);
        }
    }
}
