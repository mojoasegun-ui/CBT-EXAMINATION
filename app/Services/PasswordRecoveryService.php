<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Url;
use ExamCore\Models\PasswordReset;
use ExamCore\Models\User;
use ExamCore\Services\Auth\PasswordPolicy;

/**
 * "Forgot password" for school staff (administrators, teachers, reviewers). Students are reset by their teacher or
 * administrator, parents have no account, and the platform owner is reset from the command line, never by email.
 * Nothing here ever tells a visitor whether an email address has an account.
 */
final class PasswordRecoveryService
{
    public const TTL_SECONDS = 3600;
    private const MAX_PER_HOUR = 3;
    private const MAX_ACCOUNTS = 5;

    public function __construct(
        private readonly User $users,
        private readonly PasswordReset $resets,
        private readonly EmailService $email,
        private readonly AuditService $audit,
    ) {
    }

    /** Absolute link for emails. Built from APP_URL, never from the visitor's Host header (that would allow poisoned links). */
    public static function absolute(string $path): string
    {
        $local = Url::to($path);
        if (preg_match('#^https?://#i', $local) === 1) {
            return $local; // APP_BASE_URL is already a full address chosen by the site owner
        }
        $origin = rtrim((string) (env('APP_URL') ?: ''), '/');
        if ($origin === '') {
            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $host = preg_match('/^[A-Za-z0-9.\\-]+(:\\d+)?$/', $host) ? $host : 'localhost';
            $origin = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . $host;
        }
        return $origin . $local;
    }

    /**
     * Create reset links for every eligible staff account behind this email. Returns them (used by the support command).
     *
     * @return list<array{school:string,name:string,url:string,user_id:int}>
     */
    public function issueFor(string $email, ?string $ip): array
    {
        $email = User::normalizeEmail($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return [];
        }
        $links = [];
        foreach ($this->users->findStaffByEmail($email) as $account) {
            if (($account['status'] ?? '') !== 'active' || !in_array($account['school_status'] ?? '', ['active', 'trial'], true) || count($links) >= self::MAX_ACCOUNTS) {
                continue;
            }
            if ($this->resets->recentCount((int) $account['id'], 3600) >= self::MAX_PER_HOUR) {
                continue; // stops anyone from flooding an inbox
            }
            $token = $this->resets->issue((int) $account['id'], $ip, self::TTL_SECONDS);
            $links[] = ['school' => (string) $account['school_name'], 'name' => (string) $account['name'], 'url' => self::absolute('/reset-password/' . $token), 'user_id' => (int) $account['id']];
            $this->audit->record('auth.password_reset_requested', 'A password reset link was requested', 'user', (int) $account['id'], schoolId: (int) $account['school_id'],
                actor: ['id' => (int) $account['id'], 'name' => $account['name'], 'school_id' => (int) $account['school_id']]);
        }
        return $links;
    }

    /** Request by email: sends one message listing a link per matching account. Silent about whether anything matched. */
    public function request(string $email, ?string $ip): void
    {
        $links = $this->issueFor($email, $ip);
        if ($links !== []) {
            $this->email->sendPasswordReset(User::normalizeEmail($email), $links[0]['name'], $links);
        }
    }

    /** @return array{reset:array<string,mixed>,user:array<string,mixed>}|null */
    public function inspect(string $token): ?array
    {
        $reset = $this->resets->find($token);
        if ($reset === null) {
            return null;
        }
        $user = $this->users->find((int) $reset['user_id']);
        if ($user === null || ($user['status'] ?? '') !== 'active' || !in_array($user['role'], User::STAFF_ROLES, true)) {
            return null;
        }
        return ['reset' => $reset, 'user' => $user];
    }

    /** @return string|null an error to show, or null when the password was changed */
    public function reset(string $token, string $password, string $confirm): ?string
    {
        $found = $this->inspect($token);
        if ($found === null) {
            return 'This link has expired or was already used. Request a new one.';
        }
        $user = $found['user'];
        if ($password !== $confirm) {
            return 'The two passwords do not match.';
        }
        $error = PasswordPolicy::validate($password, 8, (string) $user['name'], (string) ($user['email'] ?? ''));
        if ($error !== null) {
            return $error;
        }
        // A new password also signs out every other device: sessions are tied to the password hash.
        $this->users->update((int) $user['id'], ['password_hash' => PasswordPolicy::hash($password), 'must_change_password' => 0, 'failed_logins' => 0, 'locked_until' => null]);
        $this->resets->invalidateFor((int) $user['id']);
        $this->audit->record('auth.password_reset', 'Password was reset with an emailed link', 'user', (int) $user['id'], schoolId: (int) $user['school_id'],
            actor: ['id' => (int) $user['id'], 'name' => $user['name'], 'school_id' => (int) $user['school_id']]);
        return null;
    }
}
