<?php
declare(strict_types=1);

namespace ExamCore\Services\Auth;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;
use ExamCore\Core\Auth\Totp;
use ExamCore\Core\Crypto;
use ExamCore\Core\Session;
use ExamCore\Models\User;

/** Authenticator-app 2FA with one-time recovery codes. Secrets are encrypted at rest. */
final class TwoFactorService
{
    private const SETUP_KEY = '_2fa_setup_secret';

    public function __construct(
        private readonly User $users,
        private readonly Session $session,
        private readonly Crypto $crypto,
        private readonly string $issuer,
        private readonly string $appKey,
    ) {
    }

    /** @param array<string, mixed> $user */
    public function isEnabled(array $user): bool
    {
        return !empty($user['two_factor_secret']) && !empty($user['two_factor_confirmed_at']);
    }

    /**
     * Start (or resume) setup. The secret lives only in the session until a code confirms it.
     *
     * @param array<string, mixed> $user
     * @return array{secret: string, uri: string, qr: string}
     */
    public function pendingSetup(array $user): array
    {
        $secret = $this->session->get(self::SETUP_KEY);
        if (!is_string($secret) || strlen($secret) < 16) {
            $secret = Totp::generateSecret();
            $this->session->set(self::SETUP_KEY, $secret);
        }
        $uri = Totp::provisioningUri($secret, (string) ($user['email'] ?? $user['username'] ?? 'account'), $this->issuer);
        $qr = (new QRCode(new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => true,
            'eccLevel' => \chillerlan\QRCode\Common\EccLevel::M,
            'addQuietzone' => true,
            'svgUseFillAttributes' => true,
        ])))->render($uri);
        return ['secret' => trim(chunk_split($secret, 4, ' ')), 'uri' => $uri, 'qr' => $qr];
    }

    /**
     * @param array<string, mixed> $user
     * @return list<string>|null the recovery codes to show once, or null if the code was wrong
     */
    public function confirmSetup(array $user, string $code): ?array
    {
        $secret = $this->session->get(self::SETUP_KEY);
        if (!is_string($secret)) {
            return null;
        }
        $step = Totp::verify($secret, $code);
        if ($step === null) {
            return null;
        }
        $codes = $this->newRecoveryCodes();
        $this->users->update((int) $user['id'], [
            'two_factor_secret' => $this->crypto->encrypt($secret),
            'two_factor_confirmed_at' => gmdate('Y-m-d H:i:s'),
            'two_factor_recovery_codes' => json_encode(array_map([$this, 'hashCode'], $codes)),
            'two_factor_last_step' => $step,
        ]);
        $this->session->remove(self::SETUP_KEY);
        return $codes;
    }

    /** Accepts a 6-digit authenticator code or an unused recovery code. @param array<string, mixed> $user */
    public function verify(array $user, string $input): bool
    {
        if (!$this->isEnabled($user)) {
            return false;
        }
        $input = trim($input);
        if (preg_match('/^\d{3}\s?\d{3}$/', $input)) {
            $last = $user['two_factor_last_step'] === null ? null : (int) $user['two_factor_last_step'];
            $step = Totp::verify($this->crypto->decrypt((string) $user['two_factor_secret']), $input, $last);
            if ($step === null) {
                return false;
            }
            $this->users->update((int) $user['id'], ['two_factor_last_step' => $step]);
            return true;
        }
        return $this->consumeRecoveryCode($user, $input);
    }

    /** @param array<string, mixed> $user */
    public function disable(array $user): void
    {
        $this->users->update((int) $user['id'], ['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null, 'two_factor_last_step' => null]);
    }

    /** @param array<string, mixed> $user */
    public function remainingRecoveryCodes(array $user): int
    {
        $codes = json_decode((string) ($user['two_factor_recovery_codes'] ?? '[]'), true);
        return is_array($codes) ? count($codes) : 0;
    }

    /** @param array<string, mixed> $user */
    private function consumeRecoveryCode(array $user, string $input): bool
    {
        $hash = $this->hashCode($input);
        $codes = json_decode((string) ($user['two_factor_recovery_codes'] ?? '[]'), true);
        if (!is_array($codes)) {
            return false;
        }
        foreach ($codes as $index => $stored) {
            if (is_string($stored) && hash_equals($stored, $hash)) {
                unset($codes[$index]);
                $this->users->update((int) $user['id'], ['two_factor_recovery_codes' => json_encode(array_values($codes))]);
                return true;
            }
        }
        return false;
    }

    /** @return list<string> e.g. "7KQ2-M9XD" */
    private function newRecoveryCodes(): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $codes = [];
        for ($n = 0; $n < 8; $n++) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $codes[] = substr($code, 0, 4) . '-' . substr($code, 4);
        }
        return $codes;
    }

    /** Recovery codes are high-entropy random strings, so a keyed fast hash is appropriate. */
    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', strtoupper(str_replace([' ', '-'], '', $code)), $this->appKey);
    }
}
