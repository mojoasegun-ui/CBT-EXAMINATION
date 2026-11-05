<?php
declare(strict_types=1);

namespace ExamCore\Core;

use ExamCore\Integrations\StudentIdentity;

final class Auth
{
    /** @param array<string, list<string>> $permissions */
    public function __construct(private readonly Session $session, private readonly array $permissions = [])
    {
    }

    public function identity(): ?StudentIdentity
    {
        $data = $this->session->get('_identity');
        if (!is_array($data)) {
            return null;
        }
        try {
            return new StudentIdentity(
                (string) ($data['external_id'] ?? ''),
                (string) ($data['internal_id'] ?? ''),
                (string) ($data['display_name'] ?? ''),
                is_array($data['roles'] ?? null) ? $data['roles'] : [],
            );
        } catch (\InvalidArgumentException) {
            $this->logout();
            return null;
        }
    }

    /** Call only after server-side credentials or a trusted host adapter have authenticated the identity. */
    public function login(StudentIdentity $identity): void
    {
        $this->session->regenerate();
        $this->session->set('_identity', [
            'external_id' => $identity->externalId,
            'internal_id' => $identity->internalId,
            'display_name' => $identity->displayName,
            'roles' => $identity->roles,
        ]);
    }

    public function check(): bool
    {
        return $this->identity() !== null;
    }

    public function hasRole(string $role): bool
    {
        return $this->identity()?->hasRole($role) ?? false;
    }

    public function can(string $permission): bool
    {
        $identity = $this->identity();
        if ($identity === null) {
            return false;
        }
        foreach ($identity->roles as $role) {
            if (in_array($permission, $this->permissions[$role] ?? [], true)) {
                return true;
            }
        }
        return false;
    }

    public function logout(): void
    {
        $this->session->remove('_identity');
        $this->session->regenerate();
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}