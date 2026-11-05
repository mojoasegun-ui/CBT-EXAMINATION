<?php
declare(strict_types=1);

namespace ExamCore\Integrations;

use InvalidArgumentException;

final class StudentIdentity
{
    /** @param list<string> $roles */
    public function __construct(
        public readonly string $externalId,
        public readonly string $internalId,
        public readonly string $displayName,
        public readonly array $roles = ['student'],
    ) {
        if (trim($externalId) === '' || trim($internalId) === '' || trim($displayName) === '' || $roles === []) {
            throw new InvalidArgumentException('Identity fields must be provided.');
        }
        foreach ($roles as $role) {
            if (!preg_match('/^[a-z][a-z0-9_-]*$/', $role)) {
                throw new InvalidArgumentException('Identity role is invalid.');
            }
        }
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }
}