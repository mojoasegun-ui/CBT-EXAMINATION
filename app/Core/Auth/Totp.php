<?php
declare(strict_types=1);

namespace ExamCore\Core\Auth;

/**
 * RFC 6238 time-based one-time passwords (Google Authenticator, Microsoft Authenticator, Authy).
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;
    private const DIGITS = 6;

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    /**
     * Accepts the current code and one step either side (±30 s of clock drift).
     * Returns the matched time-step so callers can reject replays of the same code.
     */
    public static function verify(string $secret, string $code, ?int $lastUsedStep = null, ?int $now = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (!preg_match('/^\d{6}$/', $code)) {
            return null;
        }
        $step = intdiv($now ?? time(), self::PERIOD);
        foreach ([0, -1, 1] as $offset) {
            $candidate = $step + $offset;
            if ($lastUsedStep !== null && $candidate <= $lastUsedStep) {
                continue;
            }
            if (hash_equals(self::codeAt($secret, $candidate), $code)) {
                return $candidate;
            }
        }
        return null;
    }

    public static function codeAt(string $secret, int $step): string
    {
        $key = self::base32Decode($secret);
        $hash = hash_hmac('sha1', pack('J', $step), $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);
        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function provisioningUri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
            . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=' . self::DIGITS . '&period=' . self::PERIOD;
    }

    private static function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $output = '';
        foreach (str_split($bits, 5) as $chunk) {
            $output .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }
        return $output;
    }

    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secret) ?? '');
        $bits = '';
        foreach (str_split($secret) as $char) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }
        $output = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr((int) bindec($byte));
            }
        }
        return $output;
    }
}
