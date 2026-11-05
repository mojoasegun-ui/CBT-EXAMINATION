<?php
declare(strict_types=1);

namespace ExamCore\Core\Auth;

use ExamCore\Core\Session;
use ExamCore\Models\User;

/**
 * Who is logged in for this request.
 *
 * The session stores only the user id plus a fingerprint of the password hash, so
 * changing a password (or an admin resetting it) signs out every other session.
 */
final class Guard
{
    private const KEY = '_auth';
    private const PENDING_2FA = '_auth_pending_2fa';

    /** @var array<string, mixed>|null */
    private ?array $user = null;
    private bool $loaded = false;

    public function __construct(private readonly Session $session, private readonly User $users)
    {
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        if ($this->loaded) {
            return $this->user;
        }
        $this->loaded = true;
        $state = $this->session->get(self::KEY);
        if (!is_array($state) || !is_int($state['uid'] ?? null)) {
            return null;
        }
        $user = $this->users->find($state['uid']);
        if ($user === null || $user['status'] !== 'active' || !hash_equals(self::fingerprint($user), (string) ($state['fp'] ?? ''))) {
            $this->session->remove(self::KEY);
            return null;
        }
        return $this->user = $user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function id(): ?int
    {
        $user = $this->user();
        return $user === null ? null : (int) $user['id'];
    }

    public function role(): ?string
    {
        return $this->user()['role'] ?? null;
    }

    public function schoolId(): ?int
    {
        $schoolId = $this->user()['school_id'] ?? null;
        return $schoolId === null ? null : (int) $schoolId;
    }

    /** @param array<string, mixed> $user Call only after credentials (and 2FA, if enabled) are verified. */
    public function login(array $user): void
    {
        $this->session->regenerate();
        $this->session->remove(self::PENDING_2FA);
        $this->session->set(self::KEY, ['uid' => (int) $user['id'], 'fp' => self::fingerprint($user), 'at' => time()]);
        $this->user = $user;
        $this->loaded = true;
    }

    /** Password was right but a second factor is still required. */
    public function beginTwoFactor(array $user): void
    {
        $this->session->regenerate();
        $this->session->set(self::PENDING_2FA, ['uid' => (int) $user['id'], 'fp' => self::fingerprint($user), 'at' => time()]);
    }

    /** @return array<string, mixed>|null the user awaiting a code (valid for 5 minutes) */
    public function pendingTwoFactorUser(): ?array
    {
        $pending = $this->session->get(self::PENDING_2FA);
        if (!is_array($pending) || !is_int($pending['uid'] ?? null) || time() - (int) ($pending['at'] ?? 0) > 300) {
            $this->session->remove(self::PENDING_2FA);
            return null;
        }
        $user = $this->users->find($pending['uid']);
        return $user !== null && hash_equals(self::fingerprint($user), (string) ($pending['fp'] ?? '')) ? $user : null;
    }

    /** Owner acts as a school admin for support. The original identity is kept for audit and for returning. */
    public function impersonate(array $target): void
    {
        $owner = $this->user();
        if ($owner === null) {
            return;
        }
        $this->session->regenerate();
        $this->session->set(self::KEY, [
            'uid' => (int) $target['id'],
            'fp' => self::fingerprint($target),
            'at' => time(),
            'impersonator' => ['uid' => (int) $owner['id'], 'fp' => self::fingerprint($owner)],
        ]);
        $this->user = $target;
        $this->loaded = true;
    }

    public function impersonatorId(): ?int
    {
        $state = $this->session->get(self::KEY);
        $uid = is_array($state) ? ($state['impersonator']['uid'] ?? null) : null;
        return is_int($uid) ? $uid : null;
    }

    /** @return array<string, mixed>|null the owner restored, or null if not impersonating */
    public function stopImpersonating(): ?array
    {
        $state = $this->session->get(self::KEY);
        $impersonator = is_array($state) ? ($state['impersonator'] ?? null) : null;
        if (!is_array($impersonator) || !is_int($impersonator['uid'] ?? null)) {
            return null;
        }
        $owner = $this->users->find($impersonator['uid']);
        if ($owner === null || !hash_equals(self::fingerprint($owner), (string) ($impersonator['fp'] ?? ''))) {
            $this->logout();
            return null;
        }
        $this->login($owner);
        return $owner;
    }

    /** Refresh the stored fingerprint after the current user changes their own password. */
    public function refresh(): void
    {
        $id = $this->id();
        $this->loaded = false;
        $this->user = null;
        if ($id !== null && ($user = $this->users->find($id)) !== null) {
            $state = $this->session->get(self::KEY);
            $state = is_array($state) ? $state : [];
            $this->session->set(self::KEY, ['fp' => self::fingerprint($user)] + $state);
        }
    }

    public function logout(): void
    {
        $this->session->remove(self::KEY);
        $this->session->remove(self::PENDING_2FA);
        $this->session->regenerate();
        $this->user = null;
        $this->loaded = true;
    }

    /** @param array<string, mixed> $user */
    private static function fingerprint(array $user): string
    {
        return substr(hash('sha256', (string) $user['password_hash'] . '|' . (string) $user['id']), 0, 32);
    }
}
