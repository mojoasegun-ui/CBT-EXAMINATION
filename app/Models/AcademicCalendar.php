<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/** Academic sessions (e.g. 2026/2027) and their three terms. */
final class AcademicCalendar extends Model
{
    public const TERM_NAMES = [1 => 'First Term', 2 => 'Second Term', 3 => 'Third Term'];

    /** @return list<array<string, mixed>> sessions newest first, each with 'terms' */
    public function sessions(int $schoolId): array
    {
        $sessions = $this->rows('SELECT * FROM ' . $this->table('academic_sessions') . ' WHERE school_id = ? ORDER BY starts_on DESC', [$schoolId]);
        $terms = $this->rows('SELECT * FROM ' . $this->table('terms') . ' WHERE school_id = ? ORDER BY position', [$schoolId]);
        foreach ($sessions as &$session) {
            $session['terms'] = array_values(array_filter($terms, static fn (array $t): bool => (int) $t['session_id'] === (int) $session['id']));
        }
        return $sessions;
    }

    /** @return array{session: array<string, mixed>|null, term: array<string, mixed>|null} */
    public function current(int $schoolId): array
    {
        $term = $this->row('SELECT t.*, s.name AS session_name FROM ' . $this->table('terms') . ' t
            INNER JOIN ' . $this->table('academic_sessions') . ' s ON s.id = t.session_id
            WHERE t.school_id = ? AND t.is_current = 1 LIMIT 1', [$schoolId]);
        $session = $term === null
            ? $this->row('SELECT * FROM ' . $this->table('academic_sessions') . ' WHERE school_id = ? AND is_current = 1 LIMIT 1', [$schoolId])
            : $this->row('SELECT * FROM ' . $this->table('academic_sessions') . ' WHERE id = ? AND school_id = ?', [$term['session_id'], $schoolId]);
        return ['session' => $session, 'term' => $term];
    }

    public function sessionExists(int $schoolId, string $name): bool
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('academic_sessions') . ' WHERE school_id = ? AND name = ?', [$schoolId, $name]) > 0;
    }

    /**
     * Create a session with its three terms. Term dates follow the usual Nigerian calendar
     * (Sept–Dec, Jan–Apr, Apr–Jul) and can be edited afterwards.
     */
    public function createSession(int $schoolId, int $startYear): int
    {
        $now = self::now();
        $id = $this->insertRow('academic_sessions', [
            'school_id' => $schoolId,
            'name' => $startYear . '/' . ($startYear + 1),
            'starts_on' => $startYear . '-09-01',
            'ends_on' => ($startYear + 1) . '-07-31',
            'is_current' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $dates = [
            1 => [$startYear . '-09-08', $startYear . '-12-18'],
            2 => [($startYear + 1) . '-01-08', ($startYear + 1) . '-04-10'],
            3 => [($startYear + 1) . '-04-27', ($startYear + 1) . '-07-24'],
        ];
        foreach (self::TERM_NAMES as $position => $name) {
            $this->insertRow('terms', [
                'school_id' => $schoolId, 'session_id' => $id, 'position' => $position, 'name' => $name,
                'starts_on' => $dates[$position][0], 'ends_on' => $dates[$position][1], 'is_current' => 0,
            ]);
        }
        return $id;
    }

    /** Make one term (and its session) the school's current term. */
    public function setCurrentTerm(int $schoolId, int $termId): bool
    {
        $term = $this->row('SELECT id, session_id FROM ' . $this->table('terms') . ' WHERE id = ? AND school_id = ?', [$termId, $schoolId]);
        if ($term === null) {
            return false;
        }
        $this->run('UPDATE ' . $this->table('terms') . ' SET is_current = (id = ?) WHERE school_id = ?', [$termId, $schoolId]);
        $this->run('UPDATE ' . $this->table('academic_sessions') . ' SET is_current = (id = ?), updated_at = ? WHERE school_id = ?', [$term['session_id'], self::now(), $schoolId]);
        return true;
    }

    public function updateTermDates(int $schoolId, int $termId, ?string $startsOn, ?string $endsOn): int
    {
        return $this->updateScoped('terms', $schoolId, $termId, ['starts_on' => $startsOn, 'ends_on' => $endsOn], ['starts_on', 'ends_on']);
    }

    /** Term whose dates contain $date, for picking a sensible current term. @return array<string, mixed>|null */
    public function termOn(int $schoolId, string $date): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('terms') . ' WHERE school_id = ? AND starts_on <= ? AND ends_on >= ? ORDER BY starts_on LIMIT 1', [$schoolId, $date, $date])
            ?? $this->row('SELECT * FROM ' . $this->table('terms') . ' WHERE school_id = ? AND starts_on <= ? ORDER BY starts_on DESC LIMIT 1', [$schoolId, $date]);
    }
}
