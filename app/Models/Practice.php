<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/** Practice Centre data: question catalogue (platform bank), sessions, answers, statistics, leaderboard. */
final class Practice extends Model
{
    // ---- Catalogue ------------------------------------------------------------------------------

    /** @return array<string, array{total:int, topics: array<string, array{n:int, easy:int, medium:int, hard:int}>}> */
    public function catalog(): array
    {
        $out = [];
        foreach ($this->rows('SELECT subject, topic, difficulty, COUNT(*) AS n FROM ' . $this->table('bank_questions')
            . " WHERE school_id IS NULL AND status = 'published' AND topic IS NOT NULL AND topic <> '' GROUP BY subject, topic, difficulty ORDER BY subject, topic") as $r) {
            $s = (string) $r['subject'];
            $t = (string) $r['topic'];
            $out[$s] ??= ['total' => 0, 'topics' => []];
            $out[$s]['topics'][$t] ??= ['n' => 0, 'easy' => 0, 'medium' => 0, 'hard' => 0];
            $out[$s]['topics'][$t]['n'] += (int) $r['n'];
            $out[$s]['topics'][$t][(string) $r['difficulty']] = (int) $r['n'];
            $out[$s]['total'] += (int) $r['n'];
        }
        ksort($out);
        return $out;
    }

    /** @return list<int> published platform question ids for a subject (+ optional topic, difficulty) */
    public function poolIds(string $subject, ?string $topic = null, ?string $difficulty = null, int $limit = 600): array
    {
        $sql = 'SELECT id FROM ' . $this->table('bank_questions') . " WHERE school_id IS NULL AND status = 'published' AND subject = ?";
        $params = [$subject];
        if ($topic !== null && $topic !== '') {
            $sql .= ' AND topic = ?';
            $params[] = $topic;
        }
        if ($difficulty !== null && in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $sql .= ' AND difficulty = ?';
            $params[] = $difficulty;
        }
        return array_map('intval', array_column($this->rows($sql . ' ORDER BY RAND() LIMIT ' . max(1, $limit), $params), 'id'));
    }

    /** @param list<int> $ids @return array<int, array<string, mixed>> */
    public function questions(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $rows = $this->rows('SELECT id, subject, topic, difficulty, body, image_path, option_a, option_b, option_c, option_d, option_e, correct_option, explanation FROM '
            . $this->table('bank_questions') . ' WHERE school_id IS NULL AND id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids);
        return array_column($rows, null, 'id');
    }

    /** question id => ['last_correct' => bool, 'times' => int] for what this owner has already answered. @param list<int> $ids */
    public function seen(string $ownerKey, array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $out = [];
        foreach ($this->rows('SELECT i.question_id, COUNT(*) AS times, SUBSTRING_INDEX(GROUP_CONCAT(i.is_correct ORDER BY i.answered_at DESC), \',\', 1) AS last
            FROM ' . $this->table('practice_items') . ' i JOIN ' . $this->table('practice_sessions') . ' s ON s.id = i.session_id
            WHERE s.owner_key = ? AND i.answer IS NOT NULL AND i.question_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') GROUP BY i.question_id', [$ownerKey, ...$ids]) as $r) {
            $out[(int) $r['question_id']] = ['last_correct' => (string) $r['last'] === '1', 'times' => (int) $r['times']];
        }
        return $out;
    }

    /** Questions this owner's most recent answer got wrong (not corrected later), newest first. @return list<int> */
    public function mistakeIds(string $ownerKey, int $limit): array
    {
        $items = $this->table('practice_items');
        $sessions = $this->table('practice_sessions');
        return array_map('intval', array_column($this->rows("SELECT i.question_id, MAX(i.answered_at) AS last FROM {$items} i JOIN {$sessions} s ON s.id = i.session_id
            WHERE s.owner_key = ? AND i.is_correct = 0 AND NOT EXISTS (
                SELECT 1 FROM {$items} j JOIN {$sessions} t ON t.id = j.session_id
                WHERE t.owner_key = s.owner_key AND j.question_id = i.question_id AND j.is_correct = 1 AND j.answered_at > i.answered_at)
            GROUP BY i.question_id ORDER BY last DESC LIMIT " . max(1, $limit), [$ownerKey]), 'question_id'));
    }

    public function mistakeCount(string $ownerKey): int
    {
        return count($this->mistakeIds($ownerKey, 500));
    }

    // ---- Sessions ---------------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $session
     * @param list<array{question_id:int, subject:string, topic:string, option_order:string}> $items
     */
    public function createSession(array $session, array $items): int
    {
        $now = self::now();
        $id = $this->insertRow('practice_sessions', $session + ['created_at' => $now, 'updated_at' => $now]);
        foreach (array_chunk($items, 100, true) as $chunk) {
            $values = [];
            $params = [];
            foreach ($chunk as $i => $item) {
                $values[] = '(?, ?, ?, ?, ?, ?)';
                array_push($params, $id, $i + 1, $item['question_id'], $item['subject'], $item['topic'], $item['option_order']);
            }
            $this->run('INSERT INTO ' . $this->table('practice_items') . ' (session_id, position, question_id, subject, topic, option_order) VALUES ' . implode(', ', $values), $params);
        }
        return $id;
    }

    /** Lock the session row for the rest of the current transaction. */
    public function lock(int $id): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('practice_sessions') . ' WHERE id = ? FOR UPDATE', [$id]);
    }

    public function byToken(string $token): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('practice_sessions') . ' WHERE token = ?', [$token]);
    }

    /** Items with the question content. Correct option/explanation are included; the caller decides what to expose. @return list<array<string, mixed>> */
    public function items(int $sessionId): array
    {
        return $this->rows('SELECT i.position, i.question_id, i.subject, i.topic, i.option_order, i.answer, i.is_correct, q.body, q.image_path, q.option_a, q.option_b, q.option_c, q.option_d, q.option_e,
                q.correct_option, q.explanation, q.difficulty
            FROM ' . $this->table('practice_items') . ' i JOIN ' . $this->table('bank_questions') . ' q ON q.id = i.question_id WHERE i.session_id = ? ORDER BY i.position', [$sessionId]);
    }

    /** @return array<int, string> position => letters allowed */
    public function optionOrders(int $sessionId): array
    {
        $out = [];
        foreach ($this->rows('SELECT position, option_order FROM ' . $this->table('practice_items') . ' WHERE session_id = ?', [$sessionId]) as $r) {
            $out[(int) $r['position']] = (string) $r['option_order'];
        }
        return $out;
    }

    /** @param array<int, ?string> $answers position => letter|null */
    public function saveAnswers(int $sessionId, array $answers): void
    {
        $st = $this->connection()->prepare('UPDATE ' . $this->table('practice_items') . ' SET answer = ?, answered_at = ? WHERE session_id = ? AND position = ?');
        $now = self::now();
        foreach ($answers as $pos => $letter) {
            $st->execute([$letter, $letter === null ? null : $now, $sessionId, $pos]);
        }
    }

    /** Mark every answered item against the bank and close the session. @return array{correct:int, total:int, percent:float} */
    public function finish(int $sessionId): array
    {
        $this->run('UPDATE ' . $this->table('practice_items') . ' i JOIN ' . $this->table('bank_questions') . ' q ON q.id = i.question_id
            SET i.is_correct = IF(i.answer IS NULL, NULL, i.answer = q.correct_option) WHERE i.session_id = ?', [$sessionId]);
        $row = $this->row('SELECT COUNT(*) AS total, COALESCE(SUM(is_correct = 1), 0) AS correct FROM ' . $this->table('practice_items') . ' WHERE session_id = ?', [$sessionId]) ?? ['total' => 0, 'correct' => 0];
        $total = (int) $row['total'];
        $correct = (int) $row['correct'];
        $percent = $total > 0 ? round($correct * 100 / $total, 2) : 0.0;
        $this->run('UPDATE ' . $this->table('practice_sessions') . " SET status = 'submitted', submitted_at = ?, correct_count = ?, percent = ?, updated_at = ? WHERE id = ? AND status = 'in_progress'",
            [self::now(), $correct, $percent, self::now(), $sessionId]);
        return ['correct' => $correct, 'total' => $total, 'percent' => $percent];
    }

    // ---- Statistics ---------------------------------------------------------------------------------

    /** Fold a finished session into the owner's per-topic and daily statistics. */
    public function foldStats(string $ownerKey, int $sessionId, string $day): void
    {
        $now = self::now();
        $rows = $this->rows('SELECT subject, topic, COUNT(*) AS attempts, SUM(is_correct = 1) AS correct FROM ' . $this->table('practice_items') . ' WHERE session_id = ? AND answer IS NOT NULL GROUP BY subject, topic', [$sessionId]);
        $answered = 0;
        $correctAll = 0;
        foreach ($rows as $r) {
            $this->run('INSERT INTO ' . $this->table('practice_topic_stats') . ' (owner_key, subject, topic, attempts, correct, last_at) VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE attempts = attempts + VALUES(attempts), correct = correct + VALUES(correct), last_at = VALUES(last_at)',
                [$ownerKey, $r['subject'], $r['topic'], (int) $r['attempts'], (int) $r['correct'], $now]);
            $answered += (int) $r['attempts'];
            $correctAll += (int) $r['correct'];
        }
        if ($answered > 0) {
            $this->run('INSERT INTO ' . $this->table('practice_days') . ' (owner_key, day, answered, correct) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE answered = answered + VALUES(answered), correct = correct + VALUES(correct)', [$ownerKey, $day, $answered, $correctAll]);
        }
    }

    /** @return list<array{subject:string, topic:string, attempts:int, correct:int}> */
    public function topicStats(string $ownerKey): array
    {
        return array_map(static fn (array $r): array => ['subject' => (string) $r['subject'], 'topic' => (string) $r['topic'], 'attempts' => (int) $r['attempts'], 'correct' => (int) $r['correct']],
            $this->rows('SELECT subject, topic, attempts, correct FROM ' . $this->table('practice_topic_stats') . ' WHERE owner_key = ?', [$ownerKey]));
    }

    /** @return list<array{day:string, answered:int, correct:int}> newest first */
    public function days(string $ownerKey, int $limit = 60): array
    {
        return array_map(static fn (array $r): array => ['day' => (string) $r['day'], 'answered' => (int) $r['answered'], 'correct' => (int) $r['correct']],
            $this->rows('SELECT day, answered, correct FROM ' . $this->table('practice_days') . ' WHERE owner_key = ? ORDER BY day DESC LIMIT ' . max(1, $limit), [$ownerKey]));
    }

    /** @return list<array<string, mixed>> */
    public function recentSessions(string $ownerKey, int $limit = 8): array
    {
        return $this->rows('SELECT token, mode, title, question_count, correct_count, percent, submitted_at FROM ' . $this->table('practice_sessions')
            . " WHERE owner_key = ? AND status = 'submitted' ORDER BY id DESC LIMIT " . max(1, $limit), [$ownerKey]);
    }

    /** An unfinished session this owner can resume. */
    public function openSession(string $ownerKey): ?array
    {
        return $this->row('SELECT token, title, mode, deadline_at, question_count FROM ' . $this->table('practice_sessions') . " WHERE owner_key = ? AND status = 'in_progress' ORDER BY id DESC LIMIT 1", [$ownerKey]);
    }

    // ---- Leaderboard (signed-in students) -------------------------------------------------------------

    /** Distinct questions answered correctly since $since, per student of a school (optionally one class). @return list<array{student_id:int, name:string, points:int}> */
    public function leaderboard(int $schoolId, ?int $classId, string $since, int $limit = 10): array
    {
        $sql = 'SELECT s.student_id, u.name, COUNT(DISTINCT i.question_id) AS points FROM ' . $this->table('practice_items') . ' i
            JOIN ' . $this->table('practice_sessions') . ' s ON s.id = i.session_id
            JOIN ' . $this->table('users') . ' u ON u.id = s.student_id';
        $params = [];
        if ($classId !== null) {
            $sql .= ' JOIN ' . $this->table('student_profiles') . ' sp ON sp.user_id = s.student_id AND sp.class_id = ?';
            $params[] = $classId;
        }
        $sql .= ' WHERE s.school_id = ? AND s.student_id IS NOT NULL AND i.is_correct = 1 AND i.answered_at >= ? GROUP BY s.student_id, u.name ORDER BY points DESC, u.name LIMIT ' . max(1, $limit);
        array_push($params, $schoolId, $since);
        return array_map(static fn (array $r): array => ['student_id' => (int) $r['student_id'], 'name' => (string) $r['name'], 'points' => (int) $r['points']], $this->rows($sql, $params));
    }

    public function pointsOf(int $schoolId, int $studentId, string $since): int
    {
        return (int) $this->scalar('SELECT COUNT(DISTINCT i.question_id) FROM ' . $this->table('practice_items') . ' i JOIN ' . $this->table('practice_sessions') . ' s ON s.id = i.session_id
            WHERE s.school_id = ? AND s.student_id = ? AND i.is_correct = 1 AND i.answered_at >= ?', [$schoolId, $studentId, $since]);
    }

    /** Sessions left open past their deadline (mock exams). @return list<array<string, mixed>> */
    public function overdue(int $graceSeconds, int $limit): array
    {
        return $this->rows('SELECT id, owner_key, token FROM ' . $this->table('practice_sessions') . " WHERE status = 'in_progress' AND deadline_at IS NOT NULL AND deadline_at < ? ORDER BY deadline_at LIMIT " . max(1, $limit),
            [gmdate('Y-m-d H:i:s', time() - $graceSeconds)]);
    }
}
