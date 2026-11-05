<?php
declare(strict_types=1);

namespace ExamCore\Core;

use RuntimeException;

/**
 * Authenticated encryption (AES-256-GCM) for secrets stored in the database,
 * such as two-factor seeds. Keyed by APP_KEY ("base64:<32 random bytes>").
 */
final class Crypto
{
    private const CIPHER = 'aes-256-gcm';
    private const PREFIX = 'v1:';

    private ?string $key = null;

    /** The key is validated on first use, so the app (and `php cbt key:generate`) can boot without one. */
    public function __construct(private readonly string $appKey)
    {
    }

    private function key(): string
    {
        if ($this->key !== null) {
            return $this->key;
        }
        $raw = str_starts_with($this->appKey, 'base64:') ? base64_decode(substr($this->appKey, 7), true) : false;
        if (!is_string($raw) || strlen($raw) !== 32) {
            throw new RuntimeException('APP_KEY is missing or invalid. Run: php cbt key:generate');
        }
        return $this->key = $raw;
    }

    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $this->key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed.');
        }
        return self::PREFIX . base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $payload): string
    {
        $raw = str_starts_with($payload, self::PREFIX) ? base64_decode(substr($payload, strlen(self::PREFIX)), true) : false;
        if (!is_string($raw) || strlen($raw) < 29) {
            throw new RuntimeException('Encrypted value is malformed.');
        }
        $plaintext = openssl_decrypt(substr($raw, 28), self::CIPHER, $this->key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        if ($plaintext === false) {
            throw new RuntimeException('Encrypted value could not be decrypted (wrong APP_KEY or tampered data).');
        }
        return $plaintext;
    }
}
