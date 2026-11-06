<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Models\Notification;

/**
 * Web Push for Android and iPhone (installed web app, iOS 16.4+). Messages are end-to-end encrypted to the
 * device (RFC 8291) so the push provider cannot read them, and signed with our VAPID key. A device registered
 * without keys falls back to a plain wake-up ping after which its service worker fetches the text (/push/pending).
 */
final class PushService
{
    public function __construct(private readonly Notification $notes, private readonly \ExamCore\Models\Practice $practice)
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
            $this->ping($this->notes->subscriptionsOf(array_slice(array_values(array_unique($userIds)), 0, 300)), compact('type', 'title', 'body', 'url'));
        }
    }

    /** Same as notify(), for an anonymous visitor identified by the practice cookie. */
    public function notifyGuest(string $guestKey, string $type, string $title, string $body, string $url): void
    {
        $this->notes->addForGuest($guestKey, $type, $title, $body, $url);
        if ($this->ready()) {
            $this->ping($this->notes->subscriptionsOfGuest($guestKey), compact('type', 'title', 'body', 'url'));
        }
    }

    /**
     * "Come back" nudges, from real activity: an unfinished practice, a streak about to break, or a quick question.
     * One per device per day, only in the afternoon (Nigeria time), never to someone who just visited, and it
     * stops by itself after five unanswered nudges or three weeks away.
     */
    public function engage(int $limit = 100): int
    {
        $hourWat = ((int) gmdate('G') + 1) % 24;
        if (!$this->ready() || $hourWat < 15 || $hourWat > 19) {
            return 0;
        }
        $sent = 0;
        foreach ($this->notes->engageCandidates($limit) as $sub) {
            $owner = $sub['user_id'] !== null ? 's:' . (int) $sub['user_id'] : 'g:' . $sub['guest_key'];
            [$type, $title, $body, $url] = $this->nudgeFor($owner, (int) $sub['id']);
            if ($sub['user_id'] !== null) {
                $this->notes->addFor([(int) $sub['user_id']], null, $type, $title, $body, $url);
            } else {
                $this->notes->addForGuest((string) $sub['guest_key'], $type, $title, $body, $url);
            }
            $this->notes->markEngaged((int) $sub['id']);
            $this->ping([$sub], compact('type', 'title', 'body', 'url'));
            $sent++;
        }
        return $sent;
    }

    /** @return array{0:string,1:string,2:string,3:string} type, title, body, url */
    private function nudgeFor(string $ownerKey, int $seed): array
    {
        $open = $this->practice->openSession($ownerKey);
        if ($open !== null) {
            return ['engage', 'Pick up where you left off', 'Your “' . mb_substr((string) $open['title'], 0, 60) . '” is waiting for you.', '/practice/s/' . $open['token']];
        }
        $streak = PracticeService::streak($this->practice->days($ownerKey, 60), PracticeService::today());
        if ($streak['current'] >= 2) {
            return ['engage', 'Keep your ' . $streak['current'] . '-day streak alive', 'Answer a few questions today so it does not break.', '/practice'];
        }
        $q = $this->notes->teaser((int) gmdate('z') * 31 + $seed);
        if ($q !== null) {
            return ['engage', 'Quick one: ' . $q['subject'], (string) $q['body'], '/practice/subject/' . PracticeService::slug((string) $q['subject'])];
        }
        return ['engage', 'Time for a little practice', 'Ten questions take about five minutes. Tap to start.', '/practice'];
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

    /**
     * RFC 8291 "aes128gcm" encryption of one message for one device.
     *
     * @param array<string,string> $payload
     */
    private static function encrypt(array $payload, string $p256dh, string $auth): ?string
    {
        $unb64 = static fn (string $v): string => (string) base64_decode(strtr($v, '-_', '+/') . str_repeat('=', (4 - strlen($v) % 4) % 4), true);
        $uaPublic = $unb64($p256dh);
        $authSecret = $unb64($auth);
        if (strlen($uaPublic) !== 65 || strlen($authSecret) < 8) {
            return null;
        }
        $opts = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC];
        $local = openssl_pkey_new($opts);
        if ($local === false) { // Windows/XAMPP without a findable openssl.cnf (development only)
            foreach (['C:/xampp/apache/conf/openssl.cnf', 'C:/xampp/php/extras/ssl/openssl.cnf'] as $cnf) {
                if (is_file($cnf) && ($local = openssl_pkey_new($opts + ['config' => $cnf])) !== false) {
                    break;
                }
            }
        }
        if ($local === false) {
            return null;
        }
        $d = openssl_pkey_get_details($local);
        $asPublic = "\x04" . str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT);
        $peerPem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode(hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $uaPublic), 64, "\n") . "-----END PUBLIC KEY-----\n";
        $peer = openssl_pkey_get_public($peerPem);
        $secret = $peer === false ? false : openssl_pkey_derive($peer, $local, 32);
        if ($secret === false) {
            return null;
        }
        $ikm = hash_hkdf('sha256', $secret, 32, "WebPush: info\0" . $uaPublic . $asPublic, $authSecret);
        $salt = random_bytes(16);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);
        $plain = (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\x02";
        $cipher = openssl_encrypt($plain, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        if ($cipher === false) {
            return null;
        }
        return $salt . pack('N', 4096) . chr(65) . $asPublic . $cipher . $tag;
    }

    /**
     * @param list<array<string,mixed>> $subs
     * @param array<string,string>|null $payload message to deliver inside the push (devices without keys get a plain ping)
     */
    private function ping(array $subs, ?array $payload = null): void
    {
        if ($subs === []) {
            return;
        }
        $multi = curl_multi_init();
        $handles = [];
        $sentText = [];
        $tokens = [];
        foreach ($subs as $sub) {
            $parts = parse_url((string) $sub['endpoint']);
            if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
                continue;
            }
            $aud = 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
            $tokens[$aud] ??= $this->jwt($aud);
            $body = $payload !== null && !empty($sub['p256dh']) && !empty($sub['auth']) ? self::encrypt($payload, (string) $sub['p256dh'], (string) $sub['auth']) : null;
            $headers = ['Authorization: vapid t=' . $tokens[$aud] . ', k=' . self::publicKey(), 'TTL: 86400', 'Urgency: high'];
            $headers = $body !== null ? [...$headers, 'Content-Encoding: aes128gcm', 'Content-Type: application/octet-stream', 'Content-Length: ' . strlen($body)] : [...$headers, 'Content-Length: 0'];
            $ch = curl_init((string) $sub['endpoint']);
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body ?? '', CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_HTTPHEADER => $headers]);
            curl_multi_add_handle($multi, $ch);
            $handles[(int) $sub['id']] = [$ch, $sub, $body !== null];
        }
        do {
            $status = curl_multi_exec($multi, $active);
            if ($active) {
                curl_multi_select($multi, 1.0);
            }
        } while ($active && $status === CURLM_OK);
        foreach ($handles as $id => [$ch, $sub, $withText]) {
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $ok = $code >= 200 && $code < 300;
            $this->notes->markResult($id, $ok, in_array($code, [404, 410], true));
            if ($ok && $withText) {
                $this->notes->markDeliveredFor($sub['user_id'] !== null ? (int) $sub['user_id'] : null, $sub['guest_key'] ?? null);
            }
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }
        curl_multi_close($multi);
        unset($sentText);
    }
}
