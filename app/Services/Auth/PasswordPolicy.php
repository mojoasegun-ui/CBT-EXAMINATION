<?php
declare(strict_types=1);

namespace ExamCore\Services\Auth;

final class PasswordPolicy
{
    private const COMMON = ['password', 'password1', 'password123', '12345678', '123456789', '1234567890', 'qwerty123', 'iloveyou', 'admin123', 'welcome1', 'abc12345', 'examcore', 'school123', 'nigeria1'];
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'; // No 0/O/1/l/I: easy to read aloud or dictate.

    /**
     * OWASP-recommended Argon2id parameters (19 MiB, 2 iterations, 1 lane).
     * PHP's defaults (64 MiB, 4 iterations) cost ~1 s per sign-in on modest hardware, which
     * hurts every login and makes importing thousands of students impractical.
     */
    private const ARGON_OPTIONS = ['memory_cost' => 19456, 'time_cost' => 2, 'threads' => 1];

    public static function hash(string $password): string
    {
        return defined('PASSWORD_ARGON2ID')
            ? password_hash($password, PASSWORD_ARGON2ID, self::ARGON_OPTIONS)
            : password_hash($password, PASSWORD_BCRYPT, ['cost' => 11]);
    }

    /** True for hashes made with other algorithms or parameters; they are upgraded at next sign-in. */
    public static function needsRehash(string $hash): bool
    {
        return defined('PASSWORD_ARGON2ID')
            ? password_needs_rehash($hash, PASSWORD_ARGON2ID, self::ARGON_OPTIONS)
            : password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 11]);
    }

    /** @return string|null an error message, or null when acceptable */
    public static function validate(string $password, int $minLength = 8, string ...$personal): ?string
    {
        if (mb_strlen($password) < $minLength) {
            return "Use at least {$minLength} characters.";
        }
        if (mb_strlen($password) > 128) {
            return 'Use at most 128 characters.';
        }
        if (!preg_match('/\p{L}/u', $password) || !preg_match('/\d/', $password)) {
            return 'Include at least one letter and one number.';
        }
        $lower = mb_strtolower($password);
        if (in_array($lower, self::COMMON, true)) {
            return 'That password is too common. Choose something harder to guess.';
        }
        foreach ($personal as $value) {
            $value = mb_strtolower(trim(explode('@', $value)[0]));
            if (mb_strlen($value) >= 4 && str_contains($lower, $value)) {
                return 'Do not include your name or email in the password.';
            }
        }
        return null;
    }

    /** Shorter first password for students ("Kp7w-3Mzq"), easy to type on a phone; still meets the policy. */
    public static function studentTemporary(): string
    {
        $password = substr(self::temporary(), 0, 9);
        return preg_match('/\d/', $password) ? $password : substr_replace($password, (string) random_int(2, 9), 1, 1);
    }

    /** A temporary password such as "Kp7w-3Mzq-Hn4t", shown once to the admin. */
    public static function temporary(): string
    {
        $groups = [];
        for ($g = 0; $g < 3; $g++) {
            $chunk = '';
            for ($i = 0; $i < 4; $i++) {
                $chunk .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $groups[] = $chunk;
        }
        $password = implode('-', $groups);
        // Guarantee the policy's letter+digit rule.
        return preg_match('/\d/', $password) ? $password : substr_replace($password, (string) random_int(2, 9), 1, 1);
    }
}
