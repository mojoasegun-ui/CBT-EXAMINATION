<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Models\Notification;

/**
 * Web Push for Android and iPhone (installed web app, iOS 16.4+). A push is only a wake-up "ping" signed with
 * our VAPID key; the phone's service worker then fetches the real text from the site (/push/pending). That keeps
 * message content off the push providers and avoids payload encryption.
 */
final class PushService
{
    public function __construct(private readonly Notification $notes)
    {
    }

    public static function publicKey(): string
    {
        return (string) (env('VAPID_PUBLIC') ?: '');
    }

    public function ready(): bool
    {
        return self::publicKey() !== '' && (string) (env('VAPID_PRIVATE') ?: '') !== '' && function_exists('curl_init');
    }

    private static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /** @return array{public: string, private: string} values to store in .env */
    public static function generateKeys(): array
    {
        $options = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC];
        $key = openssl_pkey_new($options);
        if ($key === false) {
            // Some Windows/XAMPP setups cannot find openssl.cnf on their own.
            foreach (['C:/xampp/apache/conf/openssl.cnf', 'C:/xampp/php/extras/ssl/openssl.cnf', 'C:/xampp/php/extras/openssl/openssl.cnf'] as $cnf) {
                if (is_file($cnf) && ($key = openssl_pkey_new($options + ['config' => $cnf])) !== false) {
                    $options['config'] = $cnf;
                    break;
                }
            }
        }
        if ($key === false) {
            throw new \RuntimeException('OpenSSL could not create an EC key.');
        }
        $details = openssl_pkey_get_details($key);
        openssl_pkey_export($key, $pem, null, isset($options['config']) ? ['config' => $options['config']] : []);
        $point = "\x04" . str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
        return ['public' => self::b64($point), 'private' => base64_encode((string) $pem)];
    }

    private function jwt(string $audience): string
    {
        $head = self::b64((string) json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::b64((string) json_encode(['aud' => $audience, 'exp' => time() + 12 * 3600, 'sub' => 'mailto:' . ((string) (env('MAIL_FROM_ADDRESS') ?: 'admin@example.com'))]));
        $pem = base64_decode((string) env('VAPID_PRIVATE'), true) ?: '';
        if (!openssl_sign($head . '.' . $claims, $der, $pem, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Could not sign the push token.');
        }
        // DER (SEQUENCE of two INTEGERs) -> raw r||s, 32 bytes each, as JWT ES256 requires.
        $off = 3;
        $rl = ord($der[$off]);
        $r = substr($der, $off + 1, $rl);
        $off += 1 + $rl + 1;
        $sl = ord($der[$off]);
        $s = substr($der, $off + 1, $sl);
        $raw = str_pad(ltrim($r, "\0"), 32, "\0", STR_PAD_LEFT) . str_pad(ltrim($s, "\0"), 32, "\0", STR_PAD_LEFT);
        return $head . '.' . $claims . '.' . self::b64($raw);
    }

    /**
     * Tell people something happened: store it for each person and wake their phones.
     *
     * @param list<int> $userIds
     */
    public function notify(array $userIds, ?int $schoolId, string $type, string $title, string $body, string $url): void
    {
        if ($userIds === []) {
            return;
        }
        $this->notes->addFor($userIds, $schoolId, $type, $title, $body, $url);
        if ($this->ready()) {
            $this->ping($this->notes->subscriptionsOf(array_slice(array_values(array_unique($userIds)), 0, 300)));
        }
    }

    /** Safety net run by the scheduler: wake phones that still have something waiting. */
    public function drain(int $limit = 150): int
    {
        if (!$this->ready()) {
            return 0;
        }
        $subs = $this->notes->subscriptionsWithPending($limit);
        $this->ping($subs);
        $this->notes->purgeOld();
        return count($subs);
    }

    /** @param list<array<string,mixed>> $subs */
    private function ping(array $subs): void
    {
        if ($subs === []) {
            return;
        }
        $multi = curl_multi_init();
        $handles = [];
        $tokens = [];
        foreach ($subs as $sub) {
            $parts = parse_url((string) $sub['endpoint']);
            if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
                continue;
            }
            $aud = 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
            $tokens[$aud] ??= $this->jwt($aud);
            $ch = curl_init((string) $sub['endpoint']);
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_POSTFIELDS => '', CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => ['Authorization: vapid t=' . $tokens[$aud] . ', k=' . self::publicKey(), 'TTL: 86400', 'Urgency: high', 'Content-Length: 0'],
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[(int) $sub['id']] = $ch;
        }
        do {
            $status = curl_multi_exec($multi, $active);
            if ($active) {
                curl_multi_select($multi, 1.0);
            }
        } while ($active && $status === CURLM_OK);
        foreach ($handles as $id => $ch) {
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $this->notes->markResult($id, $code >= 200 && $code < 300, in_array($code, [404, 410], true));
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }
        curl_multi_close($multi);
    }
}
