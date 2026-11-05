<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/** Students = users with role "student" + a student_profiles row. Always scoped to one school. */
final class Student extends Model
{
    public const STATUSES = ['active' => 'Active', 'suspended' => 'Suspended', 'graduated' => 'Graduated', 'withdrawn' => 'Withdrawn'];
    private const PROFILE_FIELDS = ['admission_no', 'class_id', 'department_id', 'gender', 'date_of_birth', 'guardian_name', 'guardian_phone', 'guardian_email', 'address', 'photo_path', 'status', 'admitted_on'];

    private function base(): string
    {
        return 'SELECT u.id, u.name, u.email, u.phone, u.status AS account_status, u.must_change_password, u.last_login_at, u.created_at,
                sp.admission_no, sp.class_id, sp.department_id, sp.gender, sp.date_of_birth, sp.guardian_name, sp.guardian_phone, sp.guardian_email,
                sp.address, sp.photo_path, sp.result_code, sp.status, sp.admitted_on, sp.school_id, c.name AS class_name, c.level_order, d.name AS department_name
            FROM ' . $this->table('student_profiles') . ' sp
            INNER JOIN ' . $this->table('users') . ' u ON u.id = sp.user_id
            LEFT JOIN ' . $this->table('school_classes') . ' c ON c.id = sp.class_id
            LEFT JOIN ' . $this->table('departments') . ' d ON d.id = sp.department_id';
    }

    /**
     * Paginated, filtered list. Uses idx (school_id, class_id, status) and the name index.
     *
     * @param array{q?: string, class_id?: int|null, department_id?: int|null, status?: string|null, gender?: string|null} $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function search(int $schoolId, array $filters, int $page, int $perPage, string $sort = 'name'): array
    {
        $kind = self::searchKind((string) ($filters['q'] ?? ''));
        if ($kind === 'surname' && !isset($filters['_mode'])) {
            $result = $this->search($schoolId, ['_mode' => 'prefix'] + $filters, $page, $perPage, $sort);
            // Nothing starts with that word (e.g. typed a first name): search inside names instead.
            return $result['total'] > 0 ? $result : $this->search($schoolId, ['_mode' => 'fulltext'] + $filters, $page, $perPage, $sort);
        }
        $nameInWhere = $kind === 'short' || ($kind === 'surname' && ($filters['_mode'] ?? '') === 'prefix');
        [$where, $params] = $this->filters($schoolId, $filters);
        $perPage = max(1, min(200, $perPage));
        $offset = max(0, ($page - 1) * $perPage);
        $users = $this->table('users');
        $profiles = $this->table('student_profiles');
        // The plain list is fastest walking the (school, role, name) index in order and stopping
        // after one page. Any search or class/department filter is more selective, so the
        // optimiser must be free to start from that filter's own index instead.
        $plainList = $kind === 'none' && empty($filters['class_id']) && empty($filters['department_id']) && ($filters['class_id'] ?? null) !== 0;
        $join = $plainList ? 'STRAIGHT_JOIN' : 'INNER JOIN';

        // Deferred join: find only this page's ids using an index that is already in the right
        // order (no sorting of the whole school), then load the full rows for those ids.
        [$from, $order] = match ($sort) {
            'admission' => ["{$profiles} sp INNER JOIN {$users} u ON u.id = sp.user_id", 'sp.admission_no'],
            'class' => ["{$profiles} sp INNER JOIN {$users} u ON u.id = sp.user_id LEFT JOIN " . $this->table('school_classes') . ' c ON c.id = sp.class_id', 'c.level_order, c.arm, u.name'],
            'newest' => ["{$users} u {$join} {$profiles} sp ON sp.user_id = u.id", 'u.id DESC'],
            default => ["{$users} u {$join} {$profiles} sp ON sp.user_id = u.id", 'u.name, u.id'],
        };
        $scoped = 'u.school_id = ? AND u.role = \'student\' AND ' . $where;
        $scopedParams = [$schoolId, ...$params];
        $ids = array_map('intval', array_column($this->rows('SELECT u.id FROM ' . $from . ' WHERE ' . $scoped . ' ORDER BY ' . $order . ' LIMIT ' . $perPage . ' OFFSET ' . $offset, $scopedParams), 'id'));
        // Counting needs the users table only when matching on names.
        $total = (int) ($nameInWhere
            ? $this->scalar('SELECT COUNT(*) FROM ' . $profiles . ' sp INNER JOIN ' . $users . ' u ON u.id = sp.user_id WHERE ' . $where, $params)
            : $this->scalar('SELECT COUNT(*) FROM ' . $profiles . ' sp WHERE ' . $where, $params));
        if ($ids === []) {
            return ['rows' => [], 'total' => $total];
        }
        $rows = array_column($this->rows($this->base() . ' WHERE sp.school_id = ? AND sp.user_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', [$schoolId, ...$ids]), null, 'id');
        return ['rows' => array_values(array_filter(array_map(static fn (int $id) => $rows[$id] ?? null, $ids))), 'total' => $total];
    }

    /** @param array<string, mixed> $filters @return list<array<string, mixed>> every match (exports, slips), capped */
    public function all(int $schoolId, array $filters, int $limit = 5000): array
    {
        [$where, $params] = $this->filters($schoolId, $filters);
        return $this->rows($this->base() . ' WHERE ' . $where . ' ORDER BY c.level_order, c.arm, u.name LIMIT ' . max(1, min(50000, $limit)), $params);
    }

    /** @return array<string, mixed>|null */
    public function find(int $schoolId, int $userId): ?array
    {
        return $this->row($this->base() . ' WHERE sp.school_id = ? AND sp.user_id = ?', [$schoolId, $userId]);
    }

    /** @param list<int> $ids @return list<array<string, mixed>> */
    public function findMany(int $schoolId, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        return $this->rows($this->base() . ' WHERE sp.school_id = ? AND sp.user_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY c.level_order, c.arm, u.name', [$schoolId, ...$ids]);
    }

    public function findByAdmission(int $schoolId, string $admissionNo): ?array
    {
        return $this->row($this->base() . ' WHERE sp.school_id = ? AND sp.admission_no = ? LIMIT 1', [$schoolId, $admissionNo]);
    }

    public function schoolOf(int $userId): ?int
    {
        $id = $this->scalar('SELECT school_id FROM ' . $this->table('student_profiles') . ' WHERE user_id = ?', [$userId]);
        return $id === null ? null : (int) $id;
    }

    public function admissionTaken(int $schoolId, string $admissionNo, int $exceptUserId = 0): bool
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('student_profiles') . ' WHERE school_id = ? AND admission_no = ? AND user_id <> ?', [$schoolId, $admissionNo, $exceptUserId]) > 0;
    }

    /** @param list<string> $numbers @return list<string> those already used in this school */
    public function existingAdmissionNumbers(int $schoolId, array $numbers): array
    {
        $numbers = array_values(array_unique(array_filter($numbers, static fn ($n): bool => $n !== '')));
        $found = [];
        foreach (array_chunk($numbers, 500) as $chunk) {
            $rows = $this->rows('SELECT admission_no FROM ' . $this->table('student_profiles') . ' WHERE school_id = ? AND admission_no IN (' . implode(',', array_fill(0, count($chunk), '?')) . ')', [$schoolId, ...$chunk]);
            array_push($found, ...array_column($rows, 'admission_no'));
        }
        return $found;
    }

    /** @param array<string, mixed> $profile */
    public function createProfile(int $schoolId, int $userId, array $profile): void
    {
        $now = self::now();
        $data = ['user_id' => $userId, 'school_id' => $schoolId, 'created_at' => $now, 'updated_at' => $now];
        foreach (self::PROFILE_FIELDS as $field) {
            if (array_key_exists($field, $profile)) {
                $data[$field] = $profile[$field];
            }
        }
        $this->insertRow('student_profiles', $data);
    }

    /** @param array<string, mixed> $profile */
    public function updateProfile(int $schoolId, int $userId, array $profile): int
    {
        return $this->updateScoped('student_profiles', $schoolId, $userId, array_intersect_key($profile, array_flip(self::PROFILE_FIELDS)) + ['updated_at' => self::now()],
            [...self::PROFILE_FIELDS, 'updated_at'], 'user_id');
    }

    public function countActive(int $schoolId): int
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('student_profiles') . ' WHERE school_id = ? AND status = \'active\'', [$schoolId]);
    }

    /** @return array<string, int> gender => count (active students) */
    public function genderSplit(int $schoolId): array
    {
        $rows = $this->rows('SELECT COALESCE(gender, \'unspecified\') AS g, COUNT(*) AS n FROM ' . $this->table('student_profiles') . ' WHERE school_id = ? AND status = \'active\' GROUP BY g', [$schoolId]);
        return array_map('intval', array_column($rows, 'n', 'g'));
    }

    /** Move every active student of one class to another (promotion), or mark them graduated. */
    public function moveClass(int $schoolId, int $fromClassId, ?int $toClassId, ?string $newStatus = null): int
    {
        if ($newStatus !== null) {
            return $this->run('UPDATE ' . $this->table('student_profiles') . ' SET status = ?, updated_at = ? WHERE school_id = ? AND class_id = ? AND status = \'active\'',
                [$newStatus, self::now(), $schoolId, $fromClassId]);
        }
        return $this->run('UPDATE ' . $this->table('student_profiles') . ' SET class_id = ?, updated_at = ? WHERE school_id = ? AND class_id = ? AND status = \'active\'',
            [$toClassId, self::now(), $schoolId, $fromClassId]);
    }

    /** Highest numeric suffix used with this prefix, for generating the next admission number. */
    public function lastSequence(int $schoolId, string $prefix): int
    {
        $rows = $this->rows('SELECT admission_no FROM ' . $this->table('student_profiles') . ' WHERE school_id = ? AND admission_no LIKE ? ORDER BY LENGTH(admission_no) DESC, admission_no DESC LIMIT 1',
            [$schoolId, addcslashes($prefix, '%_\\') . '%']);
        return isset($rows[0]) && preg_match('/(\d+)$/', (string) $rows[0]['admission_no'], $m) ? (int) $m[1] : 0;
    }

    /**
     * Index-friendly search: admission numbers and phones use prefix matches on indexes;
     * names use the FULLTEXT index with word-prefix matching ("oka" finds "Okafor").
     *
     * @return array{0: string, 1: list<string>}
     */
    /** 'none' | 'phone' | 'admission' | 'name' (FULLTEXT) | 'short' (too short for FULLTEXT) */
    private static function searchKind(string $q): string
    {
        $q = trim($q);
        return match (true) {
            $q === '' => 'none',
            (bool) preg_match('/^\+?[\d\s-]{7,}$/', $q) => 'phone',
            preg_match('/\d/', $q) && preg_match('#^[A-Za-z0-9/._-]+$#', $q) => 'admission',
            self::nameWords($q) === [] => 'short',
            // One word is usually a surname (names are stored surname-first): a prefix range on
            // the sorted (school, role, name) index answers it in a few milliseconds.
            (bool) preg_match('/^[\p{L}\'-]{3,}$/u', $q) => 'surname',
            default => 'name',
        };
    }

    /** @return list<string> words long enough for the FULLTEXT index */
    private static function nameWords(string $q): array
    {
        return array_values(array_filter(preg_split('/[^\p{L}\p{N}\']+/u', $q) ?: [], static fn (string $w): bool => mb_strlen($w) >= 3));
    }

    private function searchCondition(string $q, bool $fullText = false): array
    {
        $q = mb_substr(trim($q), 0, 80);
        // Each branch is a single prefix range on one index, so no full scans.
        switch (self::searchKind($q)) {
            case 'phone':
                $digits = preg_replace('/\D/', '', $q) ?? '';
                return ['sp.guardian_phone LIKE ?', [(str_starts_with($digits, '234') ? '0' . substr($digits, 3) : $digits) . '%']];
            case 'admission':
                return ['sp.admission_no LIKE ?', [addcslashes(strtoupper($q), '%_\\') . '%']];
            case 'short':
                return ['u.name LIKE ?', [self::like($q)]]; // 1–2 letters: plain match.
            case 'surname':
                if (!$fullText) {
                    return ['u.name LIKE ?', [addcslashes($q, '%_\\') . '%']];
                }
        }
        $words = self::nameWords($q);
        $boolean = implode(' ', array_map(static fn (string $w): string => '+' . str_replace(['"', '\''], '', $w) . '*', array_slice($words, 0, 6)));
        // A sub-query lets MariaDB/MySQL answer from the FULLTEXT index first, instead of
        // checking names row by row after scanning the whole school.
        return ['sp.user_id IN (SELECT fu.id FROM ' . $this->table('users') . ' fu WHERE MATCH(fu.name) AGAINST (? IN BOOLEAN MODE))', [$boolean]];
    }

    /** @param array<string, mixed> $filters @return array{0: string, 1: list<mixed>} */
    private function filters(int $schoolId, array $filters): array
    {
        $where = ['sp.school_id = ?'];
        $params = [$schoolId];
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            [$condition, $values] = $this->searchCondition($q, ($filters['_mode'] ?? '') === 'fulltext');
            $where[] = $condition;
            array_push($params, ...$values);
        }
        foreach (['class_id', 'department_id'] as $column) {
            if (!empty($filters[$column])) {
                $where[] = 'sp.' . $column . ' = ?';
                $params[] = (int) $filters[$column];
            }
        }
        if (($filters['class_id'] ?? null) === 0) {
            $where[] = 'sp.class_id IS NULL';
        }
        $status = $filters['status'] ?? 'active';
        if ($status !== null && $status !== '' && $status !== 'all' && array_key_exists($status, self::STATUSES)) {
            $where[] = 'sp.status = ?';
            $params[] = $status;
        }
        if (in_array($filters['gender'] ?? null, ['female', 'male'], true)) {
            $where[] = 'sp.gender = ?';
            $params[] = $filters['gender'];
        }
        return [implode(' AND ', $where), $params];
    }
}
