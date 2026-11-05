<?php
declare(strict_types=1);

namespace ExamCore\Services;

use DateTimeImmutable;
use DateTimeZone;
use ExamCore\Core\Database;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Models\Practice;

/**
 * Practice Centre engine.
 *
 *  - Learning modes (topic, quick, mistakes): instant feedback, no timer. The question set includes the answer
 *    and explanation so a session keeps working offline once opened; marking is still redone on the server.
 *  - Mock mode: timed, no feedback until submitted, answers never sent to the browser.
 *  - Question choice favours what the learner has not mastered: unseen first, then previously wrong.
 */
final class PracticeService
{
    public const GRACE_SECONDS = 120;
    public const COUNTS = [5, 10, 15, 20, 30];
    public const JAMB_PER_SUBJECT = 40;
    public const JAMB_MINUTES = 120;

    public function __construct(private readonly Database $database, private readonly Practice $practice)
    {
    }

    public static function slug(string $subject): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($subject)), '-');
    }

    /** @param array<string, mixed> $catalog */
    public static function subjectBySlug(array $catalog, string $slug): ?string
    {
        foreach (array_keys($catalog) as $subject) {
            if (self::slug((string) $subject) === $slug) {
                return (string) $subject;
            }
        }
        return null;
    }

    public static function today(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('Africa/Lagos')))->format('Y-m-d');
    }

    // ---- Starting a session -----------------------------------------------------------------------

    /**
     * @param array{key:string, student_id:?int, school_id:?int} $owner
     * @param array<string, mixed> $in
     * @return array<string, mixed> the new session row
     */
    public function start(array $owner, string $mode, array $in): array
    {
        $catalog = $this->practice->catalog();
        $asked = (int) ($in['count'] ?? 10);
        $count = in_array($asked, self::COUNTS, true) ? $asked : 10;
        $config = ['mode' => $mode];
        $minutes = null;
        $ids = [];
        $subjectOf = [];

        switch ($mode) {
            case 'topic':
                $subject = (string) ($in['subject'] ?? '');
                $topic = trim((string) ($in['topic'] ?? ''));
                if (!isset($catalog[$subject]) || ($topic !== '' && !isset($catalog[$subject]['topics'][$topic]))) {
                    throw new HttpException(422, 'Choose a subject and topic from the list.');
                }
                $difficulty = in_array($in['difficulty'] ?? '', ['easy', 'medium', 'hard'], true) ? (string) $in['difficulty'] : null;
                $title = $subject . ' · ' . ($topic !== '' ? $topic : 'mixed topics');
                $config += ['subject' => $subject, 'topic' => $topic, 'difficulty' => $difficulty];
                $ids = $this->practice->poolIds($subject, $topic, $difficulty);
                break;
            case 'quick':
                $subject = (string) ($in['subject'] ?? '');
                if ($subject !== '' && !isset($catalog[$subject])) {
                    throw new HttpException(422, 'Choose a subject from the list.');
                }
                $title = $subject !== '' ? 'Quick ' . $count . ' · ' . $subject : 'Quick ' . $count . ' · mixed subjects';
                $config += ['subject' => $subject];
                if ($subject !== '') {
                    $ids = $this->practice->poolIds($subject);
                } else {
                    $per = max(8, (int) ceil($count * 3 / max(1, count($catalog))));
                    foreach (array_keys($catalog) as $s) {
                        array_push($ids, ...$this->practice->poolIds((string) $s, null, null, $per));
                    }
                    shuffle($ids);
                }
                break;
            case 'mistakes':
                $count = in_array($asked, self::COUNTS, true) && isset($in['count']) ? $asked : 15;
                $ids = $this->practice->mistakeIds($owner['key'], $count);
                $title = 'My mistakes';
                if (count($ids) < 1) {
                    throw new HttpException(422, 'You have no mistakes to practise yet. Answer some questions first.');
                }
                break;
            case 'mock':
                [$ids, $title, $minutes, $config] = $this->mockSet($owner['key'], $catalog, $in, $config);
                break;
            default:
                throw new HttpException(422, 'Unknown practice type.');
        }
        if ($mode !== 'mock') {
            $ids = $this->prefer($owner['key'], $ids, $count);
        }
        if (count($ids) < ($mode === 'mistakes' ? 1 : 3)) {
            throw new HttpException(422, 'There are not enough questions for that choice yet. Try another topic or subject.');
        }
        $questions = $this->practice->questions($ids);
        $items = [];
        foreach ($ids as $id) {
            $q = $questions[$id] ?? null;
            if ($q === null) {
                continue;
            }
            $letters = ['A', 'B'];
            foreach (['c', 'd', 'e'] as $l) {
                if (trim((string) $q['option_' . $l]) !== '') {
                    $letters[] = strtoupper($l);
                }
            }
            shuffle($letters);
            $items[] = ['question_id' => (int) $id, 'subject' => (string) $q['subject'], 'topic' => (string) $q['topic'], 'option_order' => implode('', $letters)];
        }
        $now = time();
        $token = bin2hex(random_bytes(16));
        $id = $this->practice->createSession([
            'token' => $token, 'owner_key' => $owner['key'], 'school_id' => $owner['school_id'], 'student_id' => $owner['student_id'], 'mode' => $mode,
            'title' => mb_substr($title, 0, 160), 'config' => json_encode($config), 'status' => 'in_progress', 'started_at' => gmdate('Y-m-d H:i:s', $now),
            'deadline_at' => $minutes !== null ? gmdate('Y-m-d H:i:s', $now + $minutes * 60) : null, 'question_count' => count($items),
        ], $items);
        return (array) $this->practice->byToken($token) + ['id' => $id];
    }

    /** Unseen questions first, then ones last answered wrongly, then the rest (least-seen first). @param list<int> $ids @return list<int> */
    private function prefer(string $ownerKey, array $ids, int $count): array
    {
        $ids = array_values(array_unique($ids));
        $seen = $this->practice->seen($ownerKey, $ids);
        $rank = static function (int $id) use ($seen): int {
            if (!isset($seen[$id])) {
                return 0;
            }
            return $seen[$id]['last_correct'] ? 2 + min(50, $seen[$id]['times']) : 1;
        };
        $position = array_flip($ids); // keeps the random order inside each group
        usort($ids, static fn (int $a, int $b): int => [$rank($a), $position[$a]] <=> [$rank($b), $position[$b]]);
        return array_slice($ids, 0, $count);
    }

    /** @return array{0: list<int>, 1: string, 2: int, 3: array<string, mixed>} */
    private function mockSet(string $ownerKey, array $catalog, array $in, array $config): array
    {
        $type = ($in['type'] ?? 'single') === 'jamb' ? 'jamb' : 'single';
        if ($type === 'jamb') {
            $english = 'English Language';
            $picked = array_values(array_unique(array_filter((array) ($in['subjects'] ?? []), static fn ($s) => is_string($s) && $s !== $english)));
            if (!isset($catalog[$english]) || count($picked) !== 3) {
                throw new HttpException(422, 'A JAMB-style mock needs English Language and exactly three other subjects.');
            }
            $ids = [];
            foreach ([$english, ...$picked] as $subject) {
                if (!isset($catalog[$subject]) || $catalog[$subject]['total'] < 10) {
                    throw new HttpException(422, "“{$subject}” does not have enough questions for a mock yet.");
                }
                array_push($ids, ...$this->prefer($ownerKey, $this->practice->poolIds($subject), self::JAMB_PER_SUBJECT));
            }
            return [$ids, 'JAMB-style mock · ' . implode(', ', [$english, ...$picked]), self::JAMB_MINUTES, $config + ['type' => 'jamb', 'subjects' => [$english, ...$picked]]];
        }
        $subject = (string) ($in['subject'] ?? '');
        $n = in_array((int) ($in['questions'] ?? 40), [20, 40, 50], true) ? (int) $in['questions'] : 40;
        if (!isset($catalog[$subject])) {
            throw new HttpException(422, 'Choose a subject for the mock exam.');
        }
        $ids = $this->prefer($ownerKey, $this->practice->poolIds($subject), $n);
        $minutes = (int) ceil(count($ids) * 1.1);
        return [$ids, 'Mock exam · ' . $subject, max(10, $minutes), $config + ['type' => 'single', 'subject' => $subject]];
    }

    // ---- Running a session ----------------------------------------------------------------------------

    public static function isLearning(array $session): bool
    {
        return $session['mode'] !== 'mock';
    }

    public static function remaining(array $session): ?int
    {
        return $session['deadline_at'] === null ? null : max(0, strtotime($session['deadline_at'] . ' UTC') - time());
    }

    /** @return array<string, mixed> everything the runner needs (answers only for learning modes) */
    public function payload(array $session): array
    {
        $learning = self::isLearning($session);
        $questions = [];
        foreach ($this->practice->items((int) $session['id']) as $row) {
            $options = [];
            foreach (str_split((string) $row['option_order']) as $letter) {
                $options[] = ['key' => $letter, 'html' => rich_text((string) $row['option_' . strtolower($letter)])];
            }
            $q = [
                'n' => (int) $row['position'], 'subject' => (string) $row['subject'], 'topic' => (string) $row['topic'], 'html' => rich_text((string) $row['body']),
                'image' => $row['image_path'] ? url('/' . ltrim((string) $row['image_path'], '/')) : null, 'options' => $options, 'mine' => $row['answer'],
            ];
            if ($learning) {
                $q['answer'] = (string) $row['correct_option'];
                $q['explanation'] = $row['explanation'] ? rich_text((string) $row['explanation']) : '';
            }
            $questions[] = $q;
        }
        return [
            'token' => (string) $session['token'], 'mode' => (string) $session['mode'], 'learning' => $learning, 'title' => (string) $session['title'],
            'deadline' => $session['deadline_at'] !== null ? strtotime($session['deadline_at'] . ' UTC') : null, 'serverNow' => time(), 'questions' => $questions,
            'urls' => ['sync' => url('/practice/s/' . $session['token'] . '/sync'), 'submit' => url('/practice/s/' . $session['token'] . '/submit'), 'result' => url('/practice/s/' . $session['token'] . '/result')],
        ];
    }

    private function acceptsAnswers(array $session): bool
    {
        return $session['status'] === 'in_progress' && ($session['deadline_at'] === null || time() <= strtotime($session['deadline_at'] . ' UTC') + self::GRACE_SECONDS);
    }

    /** @param array<int|string, mixed> $answers position => letter|null @return array<int, ?string> */
    private function clean(array $session, array $answers): array
    {
        $orders = $this->practice->optionOrders((int) $session['id']);
        $out = [];
        foreach (array_slice($answers, 0, 250, true) as $pos => $letter) {
            $pos = (int) $pos;
            if (!isset($orders[$pos])) {
                continue;
            }
            $letter = is_string($letter) ? strtoupper($letter) : null;
            if ($letter !== null && (strlen($letter) !== 1 || !str_contains($orders[$pos], $letter))) {
                continue;
            }
            $out[$pos] = $letter;
        }
        return $out;
    }

    /** @param array<int|string, mixed> $answers @return array{status:string, saved:int, deadline:?int, serverNow:int} */
    public function sync(array $session, array $answers): array
    {
        $saved = 0;
        if ($this->acceptsAnswers($session)) {
            $clean = $this->clean($session, $answers);
            $this->practice->saveAnswers((int) $session['id'], $clean);
            $saved = count($clean);
        }
        $fresh = (array) $this->practice->byToken((string) $session['token']);
        return ['status' => (string) $fresh['status'], 'saved' => $saved, 'deadline' => $fresh['deadline_at'] !== null ? strtotime($fresh['deadline_at'] . ' UTC') : null, 'serverNow' => time()];
    }

    /** Mark and close. Safe to call twice: the second call only returns the stored result. @return array{correct:int, total:int, percent:float} */
    public function submit(array $session, array $answers = []): array
    {
        return $this->database->transaction(function () use ($session, $answers): array {
            $locked = $this->practice->lock((int) $session['id']);
            if ($locked === null) {
                throw new HttpException(404, 'Practice session not found.');
            }
            if ($locked['status'] === 'submitted') {
                return ['correct' => (int) $locked['correct_count'], 'total' => (int) $locked['question_count'], 'percent' => (float) $locked['percent']];
            }
            if ($answers !== [] && $this->acceptsAnswers($locked)) {
                $this->practice->saveAnswers((int) $locked['id'], $this->clean($locked, $answers));
            }
            $result = $this->practice->finish((int) $locked['id']);
            $this->practice->foldStats((string) $locked['owner_key'], (int) $locked['id'], self::today());
            return $result;
        });
    }

    public function expireOverdue(int $limit): int
    {
        $n = 0;
        foreach ($this->practice->overdue(self::GRACE_SECONDS, $limit) as $row) {
            $session = $this->practice->byToken((string) $row['token']);
            if ($session !== null) {
                $this->submit($session);
                $n++;
            }
        }
        return $n;
    }

    // ---- Results and progress -----------------------------------------------------------------------------

    /** @return array{items: list<array<string, mixed>>, topics: list<array<string, mixed>>} */
    public function result(array $session): array
    {
        $items = $this->practice->items((int) $session['id']);
        $topics = [];
        foreach ($items as $it) {
            $key = $it['subject'] . '|' . $it['topic'];
            $topics[$key] ??= ['subject' => $it['subject'], 'topic' => $it['topic'], 'total' => 0, 'correct' => 0];
            $topics[$key]['total']++;
            $topics[$key]['correct'] += (int) $it['is_correct'] === 1 ? 1 : 0;
        }
        $topics = array_values($topics);
        usort($topics, static fn (array $a, array $b): int => ($a['correct'] / max(1, $a['total'])) <=> ($b['correct'] / max(1, $b['total'])));
        return ['items' => $items, 'topics' => $topics];
    }

    /** @param array{current:int, best:int} $x */
    public static function streak(array $days, string $today): array
    {
        $set = array_flip(array_column($days, 'day'));
        $cursor = new DateTimeImmutable($today);
        if (!isset($set[$cursor->format('Y-m-d')])) {
            $cursor = $cursor->modify('-1 day'); // still alive if you practised yesterday
        }
        $current = 0;
        while (isset($set[$cursor->format('Y-m-d')])) {
            $current++;
            $cursor = $cursor->modify('-1 day');
        }
        $best = 0;
        $run = 0;
        $prev = null;
        $sorted = array_keys($set);
        sort($sorted);
        foreach ($sorted as $d) {
            $run = $prev !== null && (new DateTimeImmutable($prev))->modify('+1 day')->format('Y-m-d') === $d ? $run + 1 : 1;
            $best = max($best, $run);
            $prev = $d;
        }
        return ['current' => $current, 'best' => max($best, $current)];
    }

    /** @param array<string, mixed> $catalog @return array<string, mixed> */
    public function progress(string $ownerKey, array $catalog): array
    {
        $stats = $this->practice->topicStats($ownerKey);
        $days = $this->practice->days($ownerKey, 90);
        $today = self::today();
        $attempts = array_sum(array_column($stats, 'attempts'));
        $correct = array_sum(array_column($stats, 'correct'));
        $subjects = [];
        foreach ($stats as $s) {
            $subjects[$s['subject']] ??= ['subject' => $s['subject'], 'attempts' => 0, 'correct' => 0];
            $subjects[$s['subject']]['attempts'] += $s['attempts'];
            $subjects[$s['subject']]['correct'] += $s['correct'];
        }
        $pct = static fn (array $r): float => $r['attempts'] > 0 ? $r['correct'] * 100 / $r['attempts'] : 0.0;
        $eligible = array_values(array_filter($stats, static fn (array $s): bool => $s['attempts'] >= 5));
        $weak = array_values(array_filter($eligible, static fn (array $s): bool => $s['correct'] * 100 / $s['attempts'] < 60));
        usort($weak, static fn (array $a, array $b): int => $pct($a) <=> $pct($b));
        $strong = array_values(array_filter($eligible, static fn (array $s): bool => $s['correct'] * 100 / $s['attempts'] >= 80));
        usort($strong, static fn (array $a, array $b): int => $pct($b) <=> $pct($a));
        // What to study next: weakest topics first, then untouched topics in subjects already practised.
        $next = array_slice($weak, 0, 3);
        if (count($next) < 3) {
            $touched = array_flip(array_map(static fn (array $s): string => $s['subject'] . '|' . $s['topic'], $stats));
            foreach (array_keys($subjects) as $subject) {
                foreach (array_keys($catalog[$subject]['topics'] ?? []) as $topic) {
                    if (!isset($touched[$subject . '|' . $topic]) && count($next) < 3) {
                        $next[] = ['subject' => (string) $subject, 'topic' => (string) $topic, 'attempts' => 0, 'correct' => 0];
                    }
                }
            }
        }
        $byDay = array_column($days, 'answered', 'day');
        $series = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = (new DateTimeImmutable($today))->modify("-{$i} day")->format('Y-m-d');
            $series[] = ['date' => $d, 'count' => (int) ($byDay[$d] ?? 0)];
        }
        usort($subjects, static fn (array $a, array $b): int => $b['attempts'] <=> $a['attempts']);
        return [
            'attempts' => $attempts, 'correct' => $correct, 'accuracy' => $attempts > 0 ? round($correct * 100 / $attempts) : null,
            'subjects' => array_values($subjects), 'weak' => array_slice($weak, 0, 6), 'strong' => array_slice($strong, 0, 6), 'next' => $next,
            'series' => $series, 'streak' => self::streak($days, $today), 'mistakes' => $this->practice->mistakeCount($ownerKey),
            'sessions' => $this->practice->recentSessions($ownerKey, 6),
        ];
    }

    /** Weekly leaderboard for a student's school (and optionally class), with the student's own place. @return array<string, mixed> */
    public function leaderboard(int $schoolId, int $studentId, ?int $classId): array
    {
        $since = gmdate('Y-m-d H:i:s', time() - 7 * 86400);
        $rows = $this->practice->leaderboard($schoolId, $classId, $since, 10);
        $mine = $this->practice->pointsOf($schoolId, $studentId, $since);
        $out = [];
        foreach ($rows as $i => $r) {
            $parts = preg_split('/\s+/', trim($r['name'])) ?: [$r['name']];
            $out[] = ['rank' => $i + 1, 'name' => $parts[0] . (isset($parts[1]) ? ' ' . mb_substr($parts[count($parts) - 1], 0, 1) . '.' : ''), 'points' => $r['points'], 'me' => $r['student_id'] === $studentId];
        }
        return ['rows' => $out, 'mine' => $mine, 'inTop' => in_array(true, array_column($out, 'me'), true)];
    }
}
