<?php
declare(strict_types=1);

namespace ExamCore\Services\Auth;

use ExamCore\Core\Auth\Guard;
use ExamCore\Models\School;
use ExamCore\Models\User;
use ExamCore\Services\AuditService;

/**
 * Credential checks. Results are deliberately vague ("invalid") so an attacker cannot tell
 * whether an email exists; a dummy hash keeps response times equal either way.
 */
final class AuthService
{
    public const LOCK_AFTER = 5;
    public const LOCK_SECONDS = 900;

    private static ?string $dummyHash = null;

    public function __construct(
        private readonly User $users,
        private readonly Guard $guard,
        private readonly AuditService $audit,
        private readonly TwoFactorService $twoFactor,
        private readonly School $schools,
    ) {
    }

    /**
     * Owner Console sign-in.
     *
     * @return array{status: 'ok'|'two_factor'|'invalid'|'locked', minutes?: int}
     */
    public function attemptOwner(string $email, string $password, string $ip): array
    {
        $user = $this->users->findOwnerByEmail($email);
        return $this->complete($user === null ? [] : [$user], $password, $ip, 'console')[0];
    }

    /**
     * School staff sign-in. One email can belong to several schools; if the password matches more
     * than one active account the caller must ask which school.
     *
     * @return array{0: array{status: 'ok'|'two_factor'|'invalid'|'locked'|'choose', minutes?: int}, 1: list<array<string, mixed>>}
     */
    public function attemptStaff(string $email, string $password, string $ip, ?int $schoolId = null): array
    {
        $accounts = array_values(array_filter(
            $this->users->findStaffByEmail($email),
            static fn (array $user): bool => $schoolId === null || (int) $user['school_id'] === $schoolId,
        ));
        return $this->complete($accounts, $password, $ip, 'school');
    }

    /**
     * Student portal sign-in: school code + admission number + password.
     *
     * @return array{status: 'ok'|'invalid'|'locked'|'school_inactive', minutes?: int, school?: array<string, mixed>}
     */
    public function attemptStudent(string $schoolSlug, string $admissionNo, string $password, string $ip): array
    {
        $school = $this->schools->findBySlug(strtolower(trim($schoolSlug)));
        if ($school === null) {
            password_verify($password, self::$dummyHash ??= PasswordPolicy::hash(bin2hex(random_bytes(8))));
            return ['status' => 'invalid'];
        }
        if (in_array($school['status'], ['suspended', 'archived'], true)) {
            return ['status' => 'school_inactive', 'school' => $school];
        }
        $user = $this->users->findStudentLogin((int) $school['id'], $admissionNo);
        return $this->complete($user === null ? [] : [$user], $password, $ip, 'student portal')[0] + ['school' => $school];
    }

    /**
     * @param list<array<string, mixed>> $accounts
     * @return array{0: array{status: string, minutes?: int}, 1: list<array<string, mixed>>}
     */
    private function complete(array $accounts, string $password, string $ip, string $area): array
    {
        if ($accounts === []) {
            // Same algorithm and cost as a real check, so "no such email" is not faster.
            password_verify($password, self::$dummyHash ??= PasswordPolicy::hash(bin2hex(random_bytes(8))));
            return [['status' => 'invalid'], []];
        }

        $matches = [];
        foreach ($accounts as $user) {
            if ($this->isLocked($user)) {
                return [['status' => 'locked', 'minutes' => $this->minutesLeft($user)], []];
            }
            if (password_verify($password, (string) $user['password_hash'])) {
                $matches[] = $user;
            } else {
                $this->users->recordFailedLogin((int) $user['id'], self::LOCK_AFTER, self::LOCK_SECONDS);
            }
        }
        $matches = array_values(array_filter($matches, static fn (array $u): bool => $u['status'] === 'active'
            && ($u['school_status'] ?? 'active') !== 'archived'
            && ($u['profile_status'] ?? 'active') === 'active')); // Graduated/withdrawn students cannot sign in.
        if ($matches === []) {
            $this->audit->record('auth.failed', 'Failed ' . $area . ' sign-in for ' . ($accounts[0]['email'] ?? 'an account'), 'user', (int) $accounts[0]['id'], ['ip' => $ip],
                $accounts[0]['school_id'] === null ? null : (int) $accounts[0]['school_id']);
            return [['status' => 'invalid'], []];
        }
        if (count($matches) > 1) {
            return [['status' => 'choose'], $matches];
        }
        return [$this->signIn($matches[0], $password, $ip, $area), []];
    }

    /**
     * Second half of a successful credential check. $password is null when the password was
     * verified in an earlier step (e.g. the "which school?" picker); the rehash then waits.
     *
     * @param array<string, mixed> $user
     * @return array{status: 'ok'|'two_factor'}
     */
    public function signIn(array $user, ?string $password, string $ip, string $area): array
    {
        $update = ['failed_logins' => 0, 'locked_until' => null];
        if ($password !== null && PasswordPolicy::needsRehash((string) $user['password_hash'])) {
            $update['password_hash'] = $user['password_hash'] = PasswordPolicy::hash($password);
        }
        $this->users->update((int) $user['id'], $update);

        if ($this->twoFactor->isEnabled($user)) {
            $this->guard->beginTwoFactor($user);
            return ['status' => 'two_factor'];
        }
        $this->finish($user, $ip, $area);
        return ['status' => 'ok'];
    }

    /** @param array<string, mixed> $user */
    public function finish(array $user, string $ip, string $area): void
    {
        $this->users->update((int) $user['id'], ['last_login_at' => gmdate('Y-m-d H:i:s'), 'last_login_ip' => substr($ip, 0, 45)]);
        $this->guard->login($this->users->find((int) $user['id']) ?? $user);
        $this->audit->record('auth.login', 'Signed in to the ' . $area, 'user', (int) $user['id']);
    }

    /** @return string|null error message */
    public function changePassword(int $userId, string $current, string $new, string $confirm, bool $requireCurrent = true): ?string
    {
        $user = $this->users->find($userId);
        if ($user === null) {
            return 'Account not found.';
        }
        if ($requireCurrent && !password_verify($current, (string) $user['password_hash'])) {
            return 'Your current password is incorrect.';
        }
        if ($new !== $confirm) {
            return 'The new passwords do not match.';
        }
        if (password_verify($new, (string) $user['password_hash'])) {
            return 'Choose a password you have not used for this account.';
        }
        $error = PasswordPolicy::validate($new, $user['role'] === User::ROLE_OWNER ? 10 : 8, (string) $user['name'], (string) ($user['email'] ?? ''));
        if ($error !== null) {
            return $error;
        }
        $this->users->update($userId, ['password_hash' => PasswordPolicy::hash($new), 'must_change_password' => 0]);
        $this->guard->refresh();
        $this->audit->record('auth.password_changed', 'Changed account password', 'user', $userId);
        return null;
    }

    /** @param array<string, mixed> $user */
    private function isLocked(array $user): bool
    {
        return $user['locked_until'] !== null && strtotime((string) $user['locked_until'] . ' UTC') > time();
    }

    /** @param array<string, mixed> $user */
    private function minutesLeft(array $user): int
    {
        return max(1, (int) ceil((strtotime((string) $user['locked_until'] . ' UTC') - time()) / 60));
    }
}
