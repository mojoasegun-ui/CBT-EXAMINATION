<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/** Read-only numbers for the Console, admin and teacher dashboards. Every school query is scoped by school id. */
final class DashboardStats extends Model
{
    // ---------------------------------------------------------------- Console (all schools)

    /** @return list<array<string,mixed>> school exams being written right now, anywhere on the platform */
    public function liveSittings(int $limit = 6): array
    {
        return $this->rows('SELECT s.id, s.started_at, s.deadline_at, u.name AS student_name, sp.admission_no, sc.name AS school_name, e.title AS exam_title
            FROM ' . $this->table('exam_sittings') . ' s
            JOIN ' . $this->table('users') . ' u ON u.id = s.student_id
            LEFT JOIN ' . $this->table('student_profiles') . ' sp ON sp.user_id = s.student_id
            JOIN ' . $this->table('schools') . ' sc ON sc.id = s.school_id
            JOIN ' . $this->table('school_exams') . " e ON e.id = s.exam_id
            WHERE s.status = 'in_progress' AND s.deadline_at > ? ORDER BY s.started_at DESC LIMIT " . max(1, min(20, $limit)), [gmdate('Y-m-d H:i:s')]);
    }

    public function liveCount(?int $schoolId = null): int
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('exam_sittings') . " WHERE status = 'in_progress' AND deadline_at > ?" . ($schoolId !== null ? ' AND school_id = ?' : ''),
            $schoolId !== null ? [gmdate('Y-m-d H:i:s'), $schoolId] : [gmdate('Y-m-d H:i:s')]);
    }

    /** @return list<array{date:string,count:int}> school-exam sittings started per day */
    public function sittingsDaily(int $days, ?int $schoolId = null): array
    {
        $days = max(1, min(90, $days));
        $since = gmdate('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
        $rows = $this->rows('SELECT DATE(started_at) AS d, COUNT(*) AS n FROM ' . $this->table('exam_sittings') . ' WHERE started_at >= ?' . ($schoolId !== null ? ' AND school_id = ?' : '') . ' GROUP BY DATE(started_at)',
            $schoolId !== null ? [$since, $schoolId] : [$since]);
        $by = array_column($rows, 'n', 'd');
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = gmdate('Y-m-d', strtotime('-' . $i . ' days'));
            $out[] = ['date' => $d, 'count' => (int) ($by[$d] ?? 0)];
        }
        return $out;
    }

    public function bankCount(): int
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('bank_questions') . " WHERE school_id IS NULL AND status = 'published'");
    }

    // ---------------------------------------------------------------- School (admin or one teacher)

    /** @return list<array<string,mixed>> newest finished exams with their average, for the chart and the activity list */
    public function performance(int $schoolId, ?int $authorId, int $limit = 8): array
    {
        $rows = $this->rows('SELECT e.id, e.title, ROUND(AVG(s.percent), 1) AS avg_percent, COUNT(*) AS taken, MAX(s.submitted_at) AS last_at
            FROM ' . $this->table('school_exams') . ' e
            JOIN ' . $this->table('exam_sittings') . " s ON s.exam_id = e.id AND s.status <> 'in_progress' AND s.percent IS NOT NULL
            WHERE e.school_id = ?" . ($authorId !== null ? ' AND e.created_by = ?' : '') . ' GROUP BY e.id, e.title ORDER BY MAX(s.submitted_at) DESC LIMIT ' . max(1, min(20, $limit)),
            $authorId !== null ? [$schoolId, $authorId] : [$schoolId]);
        return array_reverse($rows); // oldest first for the chart
    }

    /** @return array{avg:?float,top:int,completed:int} over the last 30 days */
    public function schoolTotals(int $schoolId, ?int $authorId): array
    {
        $join = $authorId !== null ? ' JOIN ' . $this->table('school_exams') . ' e ON e.id = s.exam_id AND e.created_by = ?' : '';
        $params = array_filter([$authorId, $schoolId, gmdate('Y-m-d H:i:s', time() - 30 * 86400)], static fn ($v): bool => $v !== null);
        $row = $this->row('SELECT AVG(s.percent) AS avg_p, COUNT(*) AS n, COUNT(DISTINCT CASE WHEN s.percent >= 80 THEN s.student_id END) AS top
            FROM ' . $this->table('exam_sittings') . ' s' . $join . " WHERE s.school_id = ? AND s.status <> 'in_progress' AND s.percent IS NOT NULL AND s.submitted_at >= ?", array_values($params));
        return ['avg' => $row !== null && $row['avg_p'] !== null ? round((float) $row['avg_p'], 1) : null, 'top' => (int) ($row['top'] ?? 0), 'completed' => (int) ($row['n'] ?? 0)];
    }

    /** @return array{upcoming:int,live:int,total:int} exams this person (or the whole school) has scheduled */
    public function examCounts(int $schoolId, ?int $authorId): array
    {
        $now = gmdate('Y-m-d H:i:s');
        $a = $authorId !== null ? ' AND created_by = ?' : '';
        $p = static fn (array $base): array => $authorId !== null ? [...$base, $authorId] : $base;
        $table = $this->table('school_exams');
        return [
            'upcoming' => (int) $this->scalar("SELECT COUNT(*) FROM {$table} WHERE school_id = ? AND status = 'published' AND starts_at > ?{$a}", $p([$schoolId, $now])),
            'live' => (int) $this->scalar("SELECT COUNT(*) FROM {$table} WHERE school_id = ? AND status = 'published' AND starts_at <= ? AND ends_at >= ?{$a}", $p([$schoolId, $now, $now])),
            'total' => (int) $this->scalar("SELECT COUNT(*) FROM {$table} WHERE school_id = ? AND status <> 'cancelled'{$a}", $p([$schoolId])),
        ];
    }

    /** Students in the classes this teacher teaches. */
    public function teacherStudents(int $schoolId, int $teacherId): int
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('student_profiles') . " sp WHERE sp.school_id = ? AND sp.status = 'active' AND sp.class_id IN (
            SELECT class_id FROM " . $this->table('class_subjects') . ' WHERE school_id = ? AND teacher_id = ? UNION SELECT id FROM ' . $this->table('school_classes') . ' WHERE school_id = ? AND class_teacher_id = ?)',
            [$schoolId, $schoolId, $teacherId, $schoolId, $teacherId]);
    }

    /** @return list<array<string,mixed>> */
    public function schoolLive(int $schoolId, ?int $authorId, int $limit = 5): array
    {
        return $this->rows('SELECT s.started_at, s.deadline_at, u.name AS student_name, sp.admission_no, e.title AS exam_title
            FROM ' . $this->table('exam_sittings') . ' s JOIN ' . $this->table('users') . ' u ON u.id = s.student_id
            LEFT JOIN ' . $this->table('student_profiles') . ' sp ON sp.user_id = s.student_id
            JOIN ' . $this->table('school_exams') . " e ON e.id = s.exam_id
            WHERE s.school_id = ? AND s.status = 'in_progress' AND s.deadline_at > ?" . ($authorId !== null ? ' AND e.created_by = ?' : '') . ' ORDER BY s.started_at DESC LIMIT ' . max(1, min(20, $limit)),
            $authorId !== null ? [$schoolId, gmdate('Y-m-d H:i:s'), $authorId] : [$schoolId, gmdate('Y-m-d H:i:s')]);
    }
}
