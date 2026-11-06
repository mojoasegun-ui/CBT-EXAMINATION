<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/** One-time password recovery tokens. Only the hash of a token is ever stored. */
final class PasswordReset extends Model
{
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function wellFormed(string $token): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }

    /** @return string the raw token, to be sent to the owner of the account and never stored */
    public function issue(int $userId, ?string $ip, int $ttlSeconds): string
    {
        $token = bin2hex(random_bytes(32));
        $now = gmdate('Y-m-d H:i:s');
        $this->run('INSERT INTO ' . $this->table('password_resets') . ' (user_id, token_hash, expires_at, ip, created_at) VALUES (?, ?, ?, ?, ?)',
            [$userId, self::hashToken($token), gmdate('Y-m-d H:i:s', time() + $ttlSeconds), $ip, $now]);
        return $token;
    }

    /** @return array<string,mixed>|null a link that exists, is unused and has not expired */
    public function find(string $token): ?array
    {
        if (!self::wellFormed($token)) {
            return null;
        }
        return $this->row('SELECT * FROM ' . $this->table('password_resets') . ' WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?', [self::hashToken($token), gmdate('Y-m-d H:i:s')]);
    }

    /** Burn every open link of one account (after a successful reset). */
    public function invalidateFor(int $userId): void
    {
        $this->run('UPDATE ' . $this->table('password_resets') . ' SET used_at = ? WHERE user_id = ? AND used_at IS NULL', [gmdate('Y-m-d H:i:s'), $userId]);
    }

    public function recentCount(int $userId, int $seconds): int
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('password_resets') . ' WHERE user_id = ? AND created_at > ?', [$userId, gmdate('Y-m-d H:i:s', time() - $seconds)]);
    }

    public function purgeOld(): int
    {
        return $this->run('DELETE FROM ' . $this->table('password_resets') . ' WHERE created_at < ?', [gmdate('Y-m-d H:i:s', time() - 7 * 86400)]);
    }
}
