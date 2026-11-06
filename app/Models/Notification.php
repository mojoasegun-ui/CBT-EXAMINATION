<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/**
 * Per-person notification inbox and the phones/browsers registered to be pinged. A "person" is either a
 * signed-in user (user_id) or an anonymous visitor (guest_key, the Practice Centre cookie).
 */
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

    public function addForGuest(string $guestKey, string $type, string $title, string $body, string $url): void
    {
        $this->run('INSERT INTO ' . $this->table('notifications') . ' (guest_key, type, title, body, url, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$guestKey, $type, mb_substr($title, 0, 120), mb_substr($body, 0, 240), mb_substr($url, 0, 200), gmdate('Y-m-d H:i:s')]);
    }

    /** Undelivered notifications for one person (account or visitor), marked delivered as they are handed out. */
    public function takePending(?int $userId, ?string $guestKey, int $limit = 5): array
    {
        [$who, $params] = $userId !== null ? ['user_id = ?', [$userId]] : ['guest_key = ?', [(string) $guestKey]];
        if ($userId === null && $guestKey === null) {
            return [];
        }
        $rows = $this->rows('SELECT id, type, title, body, url FROM ' . $this->table('notifications') . ' WHERE ' . $who . ' AND delivered_at IS NULL AND created_at > ? ORDER BY id DESC LIMIT ' . max(1, min(10, $limit)),
            [...$params, gmdate('Y-m-d H:i:s', time() - 3 * 86400)]);
        if ($rows !== []) {
            $this->run('UPDATE ' . $this->table('notifications') . ' SET delivered_at = ? WHERE ' . $who . ' AND delivered_at IS NULL', [gmdate('Y-m-d H:i:s'), ...$params]);
        }
        return array_reverse($rows);
    }

    /** @param list<int> $userIds @return list<array<string,mixed>> */
    public function subscriptionsOf(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }
        return $this->rows('SELECT id, user_id, guest_key, endpoint, p256dh, auth FROM ' . $this->table('push_subscriptions') . ' WHERE user_id IN (' . implode(',', array_fill(0, count($userIds), '?')) . ')', array_map('intval', $userIds));
    }

    public function subscriptionsOfGuest(string $guestKey): array
    {
        return $this->rows('SELECT id, user_id, guest_key, endpoint, p256dh, auth FROM ' . $this->table('push_subscriptions') . ' WHERE guest_key = ?', [$guestKey]);
    }

    /** Subscriptions of people who still have something undelivered (the safety net behind instant sending). */
    public function subscriptionsWithPending(int $limit): array
    {
        $n = $this->table('notifications');
        return $this->rows('SELECT s.id, s.user_id, s.guest_key, s.endpoint, s.p256dh, s.auth FROM ' . $this->table('push_subscriptions') . ' s
            WHERE EXISTS (SELECT 1 FROM ' . $n . ' n WHERE n.delivered_at IS NULL AND n.created_at > ? AND ((s.user_id IS NOT NULL AND n.user_id = s.user_id) OR (s.guest_key IS NOT NULL AND n.guest_key = s.guest_key))) LIMIT ' . max(1, $limit),
            [gmdate('Y-m-d H:i:s', time() - 3600)]);
    }

    /** Devices that have been away long enough for a "come back" nudge (at most one per day, five in a row). */
    public function engageCandidates(int $limit): array
    {
        return $this->rows('SELECT id, user_id, guest_key, endpoint, p256dh, auth, engage_count FROM ' . $this->table('push_subscriptions') . '
            WHERE last_seen_at < ? AND last_seen_at > ? AND (last_engage_at IS NULL OR last_engage_at < ?) AND engage_count < 5 ORDER BY id LIMIT ' . max(1, $limit),
            [gmdate('Y-m-d H:i:s', time() - 20 * 3600), gmdate('Y-m-d H:i:s', time() - 21 * 86400), gmdate('Y-m-d H:i:s', time() - 22 * 3600)]);
    }

    public function markEngaged(int $subscriptionId): void
    {
        $this->run('UPDATE ' . $this->table('push_subscriptions') . ' SET last_engage_at = ?, engage_count = engage_count + 1 WHERE id = ?', [gmdate('Y-m-d H:i:s'), $subscriptionId]);
    }

    /** Register or refresh a device; opening the app resets the "come back" counter. */
    public function saveSubscription(?int $userId, ?int $schoolId, ?string $guestKey, string $endpoint, string $device, ?string $p256dh = null, ?string $auth = null): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->run('INSERT INTO ' . $this->table('push_subscriptions') . ' (user_id, school_id, guest_key, endpoint_hash, endpoint, device, created_at, last_seen_at, p256dh, auth) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE user_id = COALESCE(VALUES(user_id), user_id), school_id = COALESCE(VALUES(school_id), school_id), guest_key = COALESCE(VALUES(guest_key), guest_key),
                p256dh = COALESCE(VALUES(p256dh), p256dh), auth = COALESCE(VALUES(auth), auth), fails = 0, engage_count = 0, last_seen_at = VALUES(last_seen_at)',
            [$userId, $schoolId, $guestKey, hash('sha256', $endpoint), $endpoint, mb_substr($device, 0, 160), $now, $now, $p256dh, $auth]);
    }

    public function removeSubscription(?int $userId, ?string $guestKey, string $endpoint): void
    {
        $this->run('DELETE FROM ' . $this->table('push_subscriptions') . ' WHERE endpoint_hash = ? AND ((? IS NOT NULL AND user_id = ?) OR (? IS NOT NULL AND guest_key = ?))',
            [hash('sha256', $endpoint), $userId, $userId, $guestKey, $guestKey]);
    }

    public function markResult(int $subscriptionId, bool $ok, bool $gone): void
    {
        if ($gone) {
            $this->run('DELETE FROM ' . $this->table('push_subscriptions') . ' WHERE id = ?', [$subscriptionId]);
        } elseif ($ok) {
            $this->run('UPDATE ' . $this->table('push_subscriptions') . ' SET fails = 0 WHERE id = ?', [$subscriptionId]);
        } else {
            $this->run('UPDATE ' . $this->table('push_subscriptions') . ' SET fails = fails + 1 WHERE id = ?', [$subscriptionId]);
            $this->run('DELETE FROM ' . $this->table('push_subscriptions') . ' WHERE id = ? AND fails >= 8', [$subscriptionId]);
        }
    }

    /** A short, real bank question for the daily "quick one" nudge; the same seed gives the same question. */
    public function teaser(int $seed): ?array
    {
        return $this->row('SELECT subject, body FROM ' . $this->table('bank_questions') . " WHERE school_id IS NULL AND status = 'published' AND passage_id IS NULL AND image_path IS NULL
            AND CHAR_LENGTH(body) BETWEEN 25 AND 110 AND body NOT LIKE '%\n%' ORDER BY MOD(id * 7919 + ?, 10007) LIMIT 1", [$seed]);
    }

    /** The text already reached this device inside the push, so it must not be shown a second time. */
    public function markDeliveredFor(?int $userId, ?string $guestKey): void
    {
        if ($userId !== null) {
            $this->run('UPDATE ' . $this->table('notifications') . ' SET delivered_at = ? WHERE user_id = ? AND delivered_at IS NULL', [gmdate('Y-m-d H:i:s'), $userId]);
        } elseif ($guestKey !== null) {
            $this->run('UPDATE ' . $this->table('notifications') . ' SET delivered_at = ? WHERE guest_key = ? AND delivered_at IS NULL', [gmdate('Y-m-d H:i:s'), $guestKey]);
        }
    }

    public function purgeOld(): int
    {
        return $this->run('DELETE FROM ' . $this->table('notifications') . ' WHERE created_at < ?', [gmdate('Y-m-d H:i:s', time() - 30 * 86400)]);
    }
}
