<?php
namespace App\Core;

use RuntimeException;

/**
 * V7.3 — encryption for personal identifiers (e.g. NIC numbers) stored in the database.
 *
 * AES-256-GCM with a key from PERSONAL_DATA_KEY / APP_KEY (server environment) or a key file
 * created once in storage/private/personal-data.key. Back up that file together with the database:
 * without it, encrypted values cannot be read.
 */
final class PersonalDataVault
{
    private const AAD = '97-foundation-personal-data';
    private static ?string $cachedKey = null;

    private static function filePath(): string { return dirname(__DIR__, 2) . '/storage/private/personal-data.key'; }

    private static function rawKey(): string
    {
        if (self::$cachedKey !== null) return self::$cachedKey;
        $configured = trim((string)(getenv('PERSONAL_DATA_KEY') ?: config('security.personal_data_key', '') ?: getenv('APP_KEY') ?: ''));
        if (strlen($configured) >= 32) return self::$cachedKey = $configured;
        $file = self::filePath();
        if (is_file($file)) {
            $saved = trim((string)@file_get_contents($file));
            if (strlen($saved) >= 32) return self::$cachedKey = $saved;
        }
        $dir = dirname($file);
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        if (is_dir($dir) && is_writable($dir)) {
            try {
                $generated = bin2hex(random_bytes(32));
                if (@file_put_contents($file, $generated, LOCK_EX) !== false) { @chmod($file, 0600); return self::$cachedKey = $generated; }
            } catch (\Throwable) {}
        }
        return self::$cachedKey = '';
    }

    public static function ready(): bool { return strlen(self::rawKey()) >= 32 && function_exists('openssl_encrypt'); }

    private static function key(): string
    {
        $raw = self::rawKey();
        if (strlen($raw) < 32) throw new RuntimeException('Secure storage for personal data is unavailable.');
        return hash('sha256', $raw, true);
    }

    public static function encrypt(string $plain): string
    {
        if ($plain === '') return '';
        $iv = random_bytes(12); $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, self::AAD);
        if ($cipher === false) throw new RuntimeException('Personal data could not be encrypted.');
        return 'p1.' . base64_encode($iv) . '.' . base64_encode($tag) . '.' . base64_encode($cipher);
    }

    public static function decrypt(?string $encoded): string
    {
        if (!$encoded) return '';
        $parts = explode('.', $encoded);
        if (count($parts) !== 4 || $parts[0] !== 'p1') return '';
        try {
            $plain = openssl_decrypt((string)base64_decode($parts[3], true), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, (string)base64_decode($parts[1], true), (string)base64_decode($parts[2], true), self::AAD);
            return $plain === false ? '' : $plain;
        } catch (\Throwable) { return ''; }
    }

    /** Keyed hash for exact-match lookups (duplicates, admin search) without storing the value in plain text. */
    public static function fingerprint(string $value): string
    {
        return hash_hmac('sha256', strtoupper(trim($value)), self::key());
    }
}
