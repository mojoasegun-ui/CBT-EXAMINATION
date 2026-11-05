<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/** Per-person notification inbox and the phones/browsers registered to be pinged. */
final class Notification extends Model
{
    /** @param list<int> $userIds */
    public function addFor(array $userIds, ?int $schoolId, string $type, string $title, string $body, string $url): void
    {
        $now = gmdate('Y-m-d H:i:s');
        foreach (array_chunk(array_values(array_unique(array_map('intval', $userIds))), 400) as $chunk) {
            $marks = implode(',', array_fill(0, count($chunk), '(?, ?, ?, ?, ?, ?, ?)'));
            $params = [];
            foreach ($chunk as $uid) {
                array_push($params, $uid, $schoolId, $type, mb_substr($title, 0, 120), mb_substr($body, 0, 240), mb_substr($url, 0, 200), $now);
            }
            $this->run('INSERT INTO ' . $this->table('notifications') . ' (user_id, school_id, type, title, body, url, created_at) VALUES ' . $marks, $params);
        }
    }

    /** Undelivered notifications for one person, marked delivered as they are handed out. */
    public function takePending(int $userId, int $limit = 5): array
    {
        $rows = $this->rows('SELECT id, type, title, body, url FROM ' . $this->table('notifications') . ' WHERE user_id = ? AND delivered_at IS NULL AND created_at > ? ORDER BY id DESC LIMIT ' . max(1, min(10, $limit)),
            [$userId, gmdate('Y-m-d H:i:s', time() - 3 * 86400)]);
        if ($rows !== []) {
            $ids = array_map('intval', array_column($rows, 'id'));
            $this->run('UPDATE ' . $this->table('notifications') . ' SET delivered_at = ? WHERE user_id = ? AND delivered_at IS NULL', [gmdate('Y-m-d H:i:s'), $userId]);
            unset($ids);
        }
        return array_reverse($rows);
    }

    /** @param list<int> $userIds @return list<array<string,mixed>> */
    public function subscriptionsOf(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }
        return $this->rows('SELECT id, user_id, endpoint FROM ' . $this->table('push_subscriptions') . ' WHERE user_id IN (' . implode(',', array_fill(0, count($userIds), '?')) . ')', array_map('intval', $userIds));
    }

    /** Subscriptions of people who still have something undelivered (the safety net behind instant sending). */
    public function subscriptionsWithPending(int $limit): array
    {
        return $this->rows('SELECT s.id, s.user_id, s.endpoint FROM ' . $this->table('push_subscriptions') . ' s
            WHERE EXISTS (SELECT 1 FROM ' . $this->table('notifications') . ' n WHERE n.user_id = s.user_id AND n.delivered_at IS NULL AND n.created_at > ?) LIMIT ' . max(1, $limit),
            [gmdate('Y-m-d H:i:s', time() - 3600)]);
    }

    public function saveSubscription(int $userId, ?int $schoolId, string $endpoint, string $device): void
    {
        $this->run('INSERT INTO ' . $this->table('push_subscriptions') . ' (user_id, school_id, endpoint_hash, endpoint, device, created_at) VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), school_id = VALUES(school_id), fails = 0', [$userId, $schoolId, hash('sha256', $endpoint), $endpoint, mb_substr($device, 0, 160), gmdate('Y-m-d H:i:s')]);
    }

    public function removeSubscription(int $userId, string $endpoint): void
    {
        $this->run('DELETE FROM ' . $this->table('push_subscriptions') . ' WHERE user_id = ? AND endpoint_hash = ?', [$userId, hash('sha256', $endpoint)]);
    }

    public function hasSubscription(int $userId): bool
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('push_subscriptions') . ' WHERE user_id = ?', [$userId]) > 0;
    }

    public function markResult(int $subscriptionId, bool $ok, bool $gone): void
    {
        if ($gone) {
            $this->run('DELETE FROM ' . $this->table('push_subscriptions') . ' WHERE id = ?', [$subscriptionId]);
        } elseif ($ok) {
            $this->run('UPDATE ' . $this->table('push_subscriptions') . ' SET fails = 0, last_ok_at = ? WHERE id = ?', [gmdate('Y-m-d H:i:s'), $subscriptionId]);
        } else {
            $this->run('UPDATE ' . $this->table('push_subscriptions') . ' SET fails = fails + 1 WHERE id = ?', [$subscriptionId]);
            $this->run('DELETE FROM ' . $this->table('push_subscriptions') . ' WHERE id = ? AND fails >= 8', [$subscriptionId]);
        }
    }

    public function purgeOld(): int
    {
        return $this->run('DELETE FROM ' . $this->table('notifications') . ' WHERE created_at < ?', [gmdate('Y-m-d H:i:s', time() - 30 * 86400)]);
    }
}
