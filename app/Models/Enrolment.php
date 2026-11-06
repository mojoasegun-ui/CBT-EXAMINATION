<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/**
 * Class-by-session history of every student, plus the queries behind the Records pages.
 * Every query is limited to one school.
 */
final class Enrolment extends Model
{
    public const OUTCOMES = ['in_progress' => 'Current', 'promoted' => 'Promoted', 'repeated' => 'Repeated', 'graduated' => 'Graduated', 'withdrawn' => 'Withdrawn', 'suspended' => 'Suspended'];
    /** Outcomes the live student profile may still change; the rest are frozen history. */
    private const LIVE = "'in_progress','suspended','withdrawn'";

    private const OUTCOME_FROM_STATUS = "CASE sp.status WHEN 'active' THEN 'in_progress' WHEN 'graduated' THEN 'graduated' WHEN 'withdrawn' THEN 'withdrawn' ELSE 'suspended' END";

    /**
     * Make the current session's rows match the live student profiles (one student, or the whole school).
     * Rows already closed by a promotion are never rewritten.
     */
    public function sync(int $schoolId, ?int $userId = null): void
    {
        $now = self::now();
        $only = $userId !== null ? ' AND sp.user_id = ?' : '';
        $extra = $userId !== null ? [$userId] : [];
        $e = $this->table('student_enrolments');
        $this->run("UPDATE {$e} en
            JOIN " . $this->table('student_profiles') . " sp ON sp.user_id = en.student_id AND sp.school_id = en.school_id
            JOIN " . $this->table('academic_sessions') . " s ON s.id = en.session_id AND s.is_current = 1 AND s.school_id = en.school_id
            LEFT JOIN " . $this->table('school_classes') . " c ON c.id = sp.class_id AND c.school_id = sp.school_id
            SET en.class_id = sp.class_id, en.class_name = COALESCE(c.name, ''), en.level_order = c.level_order, en.outcome = " . self::OUTCOME_FROM_STATUS . ", en.updated_at = ?
            WHERE en.school_id = ? AND en.outcome IN (" . self::LIVE . ")" . $only, [$now, $schoolId, ...$extra]);
        $this->run("INSERT IGNORE INTO {$e} (school_id, student_id, session_id, class_id, class_name, level_order, outcome, created_at, updated_at)
            SELECT sp.school_id, sp.user_id, s.id, sp.class_id, COALESCE(c.name, ''), c.level_order, " . self::OUTCOME_FROM_STATUS . ", ?, ?
            FROM " . $this->table('student_profiles') . " sp
            JOIN " . $this->table('academic_sessions') . " s ON s.school_id = sp.school_id AND s.is_current = 1
            LEFT JOIN " . $this->table('school_classes') . " c ON c.id = sp.class_id AND c.school_id = sp.school_id
            WHERE sp.school_id = ?" . $only, [$now, $now, $schoolId, ...$extra]);
    }

    /**
     * End the current session for every student before promotion moves them: students in a class that is being
     * moved are "promoted", those in a class set to graduate are "graduated", the rest "repeated".
     *
     * @param array<int, int|string> $plan class id => target class id, or "graduate"
     * @return int rows closed
     */
    public function closeCurrent(int $schoolId, array $plan): int
    {
        $this->sync($schoolId);
        $e = $this->table('student_enrolments');
        $current = $this->table('academic_sessions');
        $closed = 0;
        foreach (['graduated' => array_keys(array_filter($plan, static fn ($t): bool => $t === 'graduate')), 'promoted' => array_keys(array_filter($plan, static fn ($t): bool => $t !== 'graduate'))] as $outcome => $classIds) {
            if ($classIds === []) {
                continue;
            }
            $closed += $this->run("UPDATE {$e} en JOIN {$current} s ON s.id = en.session_id AND s.is_current = 1 AND s.school_id = en.school_id
                SET en.outcome = ?, en.updated_at = ? WHERE en.school_id = ? AND en.outcome = 'in_progress' AND en.class_id IN (" . implode(',', array_fill(0, count($classIds), '?')) . ')',
                [$outcome, self::now(), $schoolId, ...array_map('intval', $classIds)]);
        }
        $closed += $this->run("UPDATE {$e} en JOIN {$current} s ON s.id = en.session_id AND s.is_current = 1 AND s.school_id = en.school_id
            SET en.outcome = 'repeated', en.updated_at = ? WHERE en.school_id = ? AND en.outcome = 'in_progress'", [self::now(), $schoolId]);
        return $closed;
    }

    /** @return list<array<string,mixed>> sessions newest first, with how many students were enrolled in each */
    public function sessions(int $schoolId): array
    {
        return $this->rows('SELECT s.id, s.name, s.starts_on, s.ends_on, s.is_current,
                (SELECT COUNT(*) FROM ' . $this->table('student_enrolments') . ' en WHERE en.session_id = s.id AND en.school_id = s.school_id) AS enrolled
            FROM ' . $this->table('academic_sessions') . ' s WHERE s.school_id = ? ORDER BY s.starts_on DESC', [$schoolId]);
    }

    /** Classes that existed (as recorded) in one session, for the filter box. @return list<array{class_id:?int,class_name:string,n:int}> */
    public function classesIn(int $schoolId, int $sessionId, ?array $onlyClassIds = null): array
    {
        $where = '';
        $params = [$schoolId, $sessionId];
        if ($onlyClassIds !== null) {
            if ($onlyClassIds === []) {
                return [];
            }
            $where = ' AND en.student_id IN (' . $this->teacherStudentSql(count($onlyClassIds)) . ')';
            $params = [...$params, ...$onlyClassIds];
        }
        return $this->rows('SELECT en.class_id, en.class_name, COUNT(*) AS n FROM ' . $this->table('student_enrolments') . ' en
            WHERE en.school_id = ? AND en.session_id = ?' . $where . ' GROUP BY en.class_id, en.class_name, en.level_order ORDER BY en.level_order, en.class_name', $params);
    }

    private function teacherStudentSql(int $n): string
    {
        return 'SELECT sp.user_id FROM ' . $this->table('student_profiles') . ' sp WHERE sp.class_id IN (' . implode(',', array_fill(0, $n, '?')) . ')';
    }

    /**
     * Students enrolled in one session, with their average for that session.
     *
     * @param array{class?:int,q?:string,outcome?:string} $filters
     * @param list<int>|null $teacherClassIds when set (a teacher), only students who are currently in these classes
     * @return array{rows:list<array<string,mixed>>,total:int}
     */
    public function inSession(int $schoolId, int $sessionId, array $filters, int $page, int $perPage, ?array $teacherClassIds = null): array
    {
        $where = ['en.school_id = ?', 'en.session_id = ?'];
        $params = [$schoolId, $sessionId];
        if (!empty($filters['class'])) {
            $where[] = 'en.class_id = ?';
            $params[] = (int) $filters['class'];
        }
        if (!empty($filters['outcome']) && isset(self::OUTCOMES[$filters['outcome']])) {
            $where[] = 'en.outcome = ?';
            $params[] = $filters['outcome'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(u.name LIKE ? OR sp.admission_no LIKE ?)';
            $like = '%' . addcslashes((string) $filters['q'], '%_\\') . '%';
            array_push($params, $like, $like);
        }
        if ($teacherClassIds !== null) {
            if ($teacherClassIds === []) {
                return ['rows' => [], 'total' => 0];
            }
            $where[] = 'en.student_id IN (' . $this->teacherStudentSql(count($teacherClassIds)) . ')';
            $params = [...$params, ...$teacherClassIds];
        }
        $from = ' FROM ' . $this->table('student_enrolments') . ' en JOIN ' . $this->table('users') . ' u ON u.id = en.student_id
            JOIN ' . $this->table('student_profiles') . ' sp ON sp.user_id = en.student_id AND sp.school_id = en.school_id WHERE ' . implode(' AND ', $where);
        $total = (int) $this->scalar('SELECT COUNT(*)' . $from, $params);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = $this->rows('SELECT en.student_id, en.class_name, en.outcome, u.name, sp.admission_no, sp.status AS profile_status,
                (SELECT ROUND(AVG(s.percent), 1) FROM ' . $this->table('exam_sittings') . ' s JOIN ' . $this->table('school_exams') . ' e ON e.id = s.exam_id
                    JOIN ' . $this->table('terms') . ' t ON t.id = e.term_id
                    WHERE s.student_id = en.student_id AND s.school_id = en.school_id AND t.session_id = en.session_id AND s.status <> \'in_progress\' AND s.percent IS NOT NULL) AS avg_percent'
            . $from . ' ORDER BY en.level_order, en.class_name, u.name LIMIT ' . (int) $perPage . ' OFFSET ' . $offset, $params);
        return ['rows' => $rows, 'total' => $total];
    }

    /** @return list<array<string,mixed>> the student's class in every session, newest first */
    public function timeline(int $schoolId, int $studentId): array
    {
        return $this->rows('SELECT en.session_id, s.name AS session_name, s.is_current, en.class_name, en.outcome FROM ' . $this->table('student_enrolments') . ' en
            JOIN ' . $this->table('academic_sessions') . ' s ON s.id = en.session_id AND s.school_id = en.school_id
            WHERE en.school_id = ? AND en.student_id = ? ORDER BY s.starts_on DESC', [$schoolId, $studentId]);
    }

    /** @return list<array<string,mixed>> every finished exam of the student, with its term and session */
    public function results(int $schoolId, int $studentId): array
    {
        return $this->rows('SELECT s.id AS sitting_id, s.percent, s.score, s.question_count, s.status, s.submitted_at, e.id AS exam_id, e.title, e.type, e.term_id,
                t.name AS term_name, t.session_id, ses.name AS session_name
            FROM ' . $this->table('exam_sittings') . ' s JOIN ' . $this->table('school_exams') . ' e ON e.id = s.exam_id
            LEFT JOIN ' . $this->table('terms') . ' t ON t.id = e.term_id LEFT JOIN ' . $this->table('academic_sessions') . ' ses ON ses.id = t.session_id
            WHERE s.school_id = ? AND s.student_id = ? AND s.status <> \'in_progress\' ORDER BY s.submitted_at DESC, s.id DESC LIMIT 500', [$schoolId, $studentId]);
    }

    /** Class ids a teacher is responsible for today (class teacher or subject teacher). @return list<int> */
    public function teacherClassIds(int $schoolId, int $teacherId): array
    {
        $rows = $this->rows('SELECT class_id AS id FROM ' . $this->table('class_subjects') . ' WHERE school_id = ? AND teacher_id = ?
            UNION SELECT id FROM ' . $this->table('school_classes') . ' WHERE school_id = ? AND class_teacher_id = ?', [$schoolId, $teacherId, $schoolId, $teacherId]);
        return array_values(array_unique(array_map('intval', array_column($rows, 'id'))));
    }

    /** Whether this student currently sits in one of the given classes (teacher access rule). */
    public function inClasses(int $schoolId, int $studentId, array $classIds): bool
    {
        if ($classIds === []) {
            return false;
        }
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('student_profiles') . ' WHERE school_id = ? AND user_id = ? AND class_id IN (' . implode(',', array_fill(0, count($classIds), '?')) . ')',
            [$schoolId, $studentId, ...$classIds]) > 0;
    }
}
