<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Crypto;
use ExamCore\Core\Database;
use ExamCore\Core\ImageUpload;
use ExamCore\Core\Spreadsheet\Spreadsheet;
use ExamCore\Models\Department;
use ExamCore\Models\ImportBatch;
use ExamCore\Models\School;
use ExamCore\Models\SchoolClass;
use ExamCore\Models\Student;
use ExamCore\Models\User;
use ExamCore\Services\Auth\PasswordPolicy;
use RuntimeException;

/** Student records for one school: enrolment, profiles, imports, promotion and exports. */
final class StudentService
{
    public const IMPORT_CHUNK = 25; // ~25 password hashes per request keeps every request short on shared hosting.

    /** Spreadsheet header aliases → field. Lower-cased, spaces/punctuation removed before matching. */
    private const HEADER_ALIASES = [
        'name' => ['name', 'fullname', 'studentname', 'nameofstudent', 'studentsname', 'pupilname'],
        'surname' => ['surname', 'lastname', 'familyname'],
        'first_name' => ['firstname', 'givenname', 'forename'],
        'other_names' => ['othernames', 'othername', 'middlename', 'middlenames'],
        'admission_no' => ['admissionno', 'admissionnumber', 'admno', 'admissionid', 'regno', 'regnumber', 'registrationnumber', 'registrationno', 'studentid', 'studentno'],
        'class' => ['class', 'classname', 'classarm', 'form', 'level'],
        'gender' => ['gender', 'sex'],
        'date_of_birth' => ['dateofbirth', 'dob', 'birthdate', 'birthday'],
        'department' => ['department', 'dept', 'stream'],
        'guardian_name' => ['guardianname', 'parentname', 'guardian', 'parent', 'parentguardian', 'parentguardianname', 'nameofparent'],
        'guardian_phone' => ['guardianphone', 'parentphone', 'phone', 'phonenumber', 'parentphonenumber', 'guardianphonenumber', 'mobile', 'contact'],
        'guardian_email' => ['guardianemail', 'parentemail', 'email', 'emailaddress'],
        'address' => ['address', 'homeaddress', 'residentialaddress'],
    ];

    public function __construct(
        private readonly Database $database,
        private readonly Student $students,
        private readonly User $users,
        private readonly SchoolClass $classes,
        private readonly Department $departments,
        private readonly School $schools,
        private readonly ImportBatch $imports,
        private readonly Crypto $crypto,
        private readonly ImageUpload $images,
        private readonly AuditService $audit,
    ) {
    }

    // ---- Single student ---------------------------------------------------------------

    /**
     * @param array<string, mixed> $input
     * @return array{errors: array<string, string>, data: array<string, mixed>}
     */
    public function validate(int $schoolId, array $input, int $exceptUserId = 0): array
    {
        $errors = [];
        $name = trim(preg_replace('/\s+/', ' ', (string) ($input['name'] ?? '')) ?? '');
        if (mb_strlen($name) < 3 || mb_strlen($name) > 120) {
            $errors['name'] = 'Enter the student’s full name (surname and first name).';
        }
        $admission = strtoupper(trim((string) ($input['admission_no'] ?? '')));
        if ($admission !== '' && !preg_match('#^[A-Z0-9/._-]{2,40}$#', $admission)) {
            $errors['admission_no'] = 'Use letters, numbers, slashes, dots or dashes (up to 40).';
        } elseif ($admission !== '' && $this->students->admissionTaken($schoolId, $admission, $exceptUserId)) {
            $errors['admission_no'] = 'Another student already has this admission number.';
        }
        $classId = (int) ($input['class_id'] ?? 0);
        if ($classId > 0 && $this->classes->find($schoolId, $classId) === null) {
            $errors['class_id'] = 'Choose a class from the list.';
        }
        $departmentId = (int) ($input['department_id'] ?? 0);
        if ($departmentId > 0 && $this->departments->find($schoolId, $departmentId) === null) {
            $errors['department_id'] = 'Choose a department from the list.';
        }
        $gender = (string) ($input['gender'] ?? '');
        if ($gender !== '' && !in_array($gender, ['female', 'male'], true)) {
            $errors['gender'] = 'Choose female or male.';
        }
        $dob = trim((string) ($input['date_of_birth'] ?? ''));
        if ($dob !== '' && (Spreadsheet::toDate($dob) === null || Spreadsheet::toDate($dob) > gmdate('Y-m-d'))) {
            $errors['date_of_birth'] = 'Enter a valid date of birth in the past.';
        }
        $email = trim((string) ($input['guardian_email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['guardian_email'] = 'Enter a valid email address or leave it blank.';
        }
        $phone = self::normalizePhone((string) ($input['guardian_phone'] ?? ''));
        if (trim((string) ($input['guardian_phone'] ?? '')) !== '' && $phone === null) {
            $errors['guardian_phone'] = 'Enter a Nigerian number like 0803 123 4567 or +234 803 123 4567.';
        }
        $status = (string) ($input['status'] ?? 'active');
        if (!array_key_exists($status, Student::STATUSES)) {
            $errors['status'] = 'Choose a status.';
        }
        $optional = static fn (string $key, int $max): ?string => ($v = trim((string) ($input[$key] ?? ''))) === '' ? null : mb_substr($v, 0, $max);

        return ['errors' => $errors, 'data' => [
            'name' => $name,
            'admission_no' => $admission,
            'class_id' => $classId > 0 ? $classId : null,
            'department_id' => $departmentId > 0 ? $departmentId : null,
            'gender' => $gender !== '' ? $gender : null,
            'date_of_birth' => $dob !== '' ? Spreadsheet::toDate($dob) : null,
            'guardian_name' => $optional('guardian_name', 120),
            'guardian_phone' => $phone,
            'guardian_email' => $email !== '' ? strtolower($email) : null,
            'address' => $optional('address', 255),
            'status' => $status,
            'admitted_on' => Spreadsheet::toDate((string) ($input['admitted_on'] ?? '')),
        ]];
    }

    /**
     * @param array<string, mixed> $data validated
     * @return array{user_id: int, admission_no: string, password: string}
     */
    public function create(int $schoolId, array $data): array
    {
        $this->assertCapacity($schoolId, 1);
        $password = PasswordPolicy::studentTemporary();
        $result = $this->database->transaction(function () use ($schoolId, $data, $password): array {
            $admission = $data['admission_no'] !== '' ? $data['admission_no'] : $this->nextAdmissionNumber($schoolId);
            $userId = $this->users->create([
                'school_id' => $schoolId, 'role' => User::ROLE_STUDENT, 'name' => $data['name'], 'username' => $admission,
                'password_hash' => PasswordPolicy::hash($password), 'must_change_password' => 1,
            ]);
            $this->students->createProfile($schoolId, $userId, ['admission_no' => $admission] + array_diff_key($data, ['name' => 1, 'admission_no' => 1]));
            return ['user_id' => $userId, 'admission_no' => $admission];
        });
        $this->audit->record('student.created', 'Enrolled ' . $data['name'] . ' (' . $result['admission_no'] . ')', 'user', $result['user_id'], [], $schoolId);
        return $result + ['password' => $password];
    }

    /** @param array<string, mixed> $data validated */
    public function update(int $schoolId, int $userId, array $data): void
    {
        $current = $this->students->find($schoolId, $userId) ?? throw new RuntimeException('Student not found.');
        $this->database->transaction(function () use ($schoolId, $userId, $data, $current): void {
            $admission = $data['admission_no'] !== '' ? $data['admission_no'] : (string) $current['admission_no'];
            $this->users->update($userId, ['name' => $data['name']]);
            $this->users->setUsername($schoolId, $userId, $admission);
            $this->students->updateProfile($schoolId, $userId, ['admission_no' => $admission] + array_diff_key($data, ['name' => 1, 'admission_no' => 1]));
        });
        $this->audit->record('student.updated', 'Updated ' . $data['name'] . '’s record', 'user', $userId, [], $schoolId);
    }

    public function resetPassword(int $schoolId, int $userId): string
    {
        $student = $this->students->find($schoolId, $userId) ?? throw new RuntimeException('Student not found.');
        $password = PasswordPolicy::studentTemporary();
        $this->users->update($userId, ['password_hash' => PasswordPolicy::hash($password), 'must_change_password' => 1, 'failed_logins' => 0, 'locked_until' => null]);
        $this->audit->record('student.password_reset', 'Reset the password for ' . $student['name'], 'user', $userId, [], $schoolId);
        return $password;
    }

    /** @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file */
    public function uploadPhoto(int $schoolId, int $userId, array $file): ?string
    {
        $student = $this->students->find($schoolId, $userId) ?? throw new RuntimeException('Student not found.');
        $directory = 'uploads/schools/' . $schoolId . '/students';
        $stored = $this->images->store($file, $directory, 'p' . $userId, 2_097_152, 96, 4000);
        if ($stored['error'] !== null) {
            return $stored['error'];
        }
        $this->students->updateProfile($schoolId, $userId, ['photo_path' => $stored['path']]);
        $this->images->delete($student['photo_path'], $directory);
        return null;
    }

    /**
     * End-of-session promotion. $plan maps class id → target class id, or "graduate".
     *
     * @param array<int, int|string> $plan
     * @return array{moved: int, graduated: int}
     */
    public function promote(int $schoolId, array $plan): array
    {
        $validClassIds = array_map(static fn (array $c): int => (int) $c['id'], $this->classes->all($schoolId));
        // Process the highest classes first so SS 2 → SS 3 does not collide with SS 3 → graduate.
        $order = [];
        foreach ($this->classes->all($schoolId) as $class) {
            $order[(int) $class['id']] = (int) $class['level_order'];
        }
        uksort($plan, static fn ($a, $b): int => ($order[(int) $b] ?? 0) <=> ($order[(int) $a] ?? 0));

        $result = $this->database->transaction(function () use ($schoolId, $plan, $validClassIds): array {
            $moved = $graduated = 0;
            foreach ($plan as $from => $to) {
                if (!in_array((int) $from, $validClassIds, true)) {
                    continue;
                }
                if ($to === 'graduate') {
                    $graduated += $this->students->moveClass($schoolId, (int) $from, null, 'graduated');
                } elseif ((int) $to > 0 && in_array((int) $to, $validClassIds, true) && (int) $to !== (int) $from) {
                    $moved += $this->students->moveClass($schoolId, (int) $from, (int) $to);
                }
            }
            return ['moved' => $moved, 'graduated' => $graduated];
        });
        $this->audit->record('student.promotion', sprintf('Promoted %d student(s) and graduated %d', $result['moved'], $result['graduated']), meta: $result, schoolId: $schoolId);
        return $result;
    }

    // ---- Import -------------------------------------------------------------------------

    /**
     * Read and validate a spreadsheet without saving anything.
     *
     * @return array{valid: list<array<string, mixed>>, errors: list<array{row: int, messages: list<string>}>, columns: array<string, int>, total: int}
     */
    public function analyseImport(int $schoolId, string $path, string $fileName): array
    {
        $rows = Spreadsheet::read($path, $fileName);
        if (count($rows) < 2) {
            throw new RuntimeException('The file is empty. Put the column names in row 1 and one student per row below it.');
        }
        $columns = $this->mapHeaders(array_shift($rows));
        if (!isset($columns['name']) && !isset($columns['surname'])) {
            throw new RuntimeException('No "Name" column found. Download the template to see the expected columns.');
        }

        $classes = [];
        foreach ($this->classes->all($schoolId) as $class) {
            $classes[strtolower(str_replace(' ', '', (string) $class['name']))] = (int) $class['id'];
        }
        $departments = [];
        foreach ($this->departments->all($schoolId) as $department) {
            $departments[strtolower((string) $department['name'])] = (int) $department['id'];
        }
        $cell = static fn (array $row, string $field): string => isset($columns[$field]) ? trim((string) ($row[$columns[$field]] ?? '')) : '';

        $candidates = [];
        $errors = [];
        $seen = [];
        foreach ($rows as $index => $row) {
            $line = $index + 2; // Spreadsheet row number as the user sees it.
            if (implode('', $row) === '') {
                continue;
            }
            $name = $cell($row, 'name');
            if ($name === '') {
                $name = trim(implode(' ', array_filter([$cell($row, 'surname'), $cell($row, 'first_name'), $cell($row, 'other_names')])));
            }
            $messages = [];
            $classText = $cell($row, 'class');
            $classId = null;
            if ($classText !== '') {
                $classId = $classes[strtolower(str_replace(' ', '', $classText))] ?? null;
                if ($classId === null) {
                    $messages[] = 'Class "' . $classText . '" does not exist. Create it first or fix the spelling (e.g. "SS 2 A").';
                }
            }
            $departmentText = $cell($row, 'department');
            $departmentId = $departmentText === '' ? null : ($departments[strtolower($departmentText)] ?? null);
            if ($departmentText !== '' && $departmentId === null) {
                $messages[] = 'Department "' . $departmentText . '" does not exist.';
            }
            $check = $this->validate($schoolId, [
                'name' => self::titleCase($name),
                'admission_no' => $cell($row, 'admission_no'),
                'gender' => self::normalizeGender($cell($row, 'gender')),
                'date_of_birth' => $cell($row, 'date_of_birth'),
                'guardian_name' => $cell($row, 'guardian_name'),
                'guardian_phone' => $cell($row, 'guardian_phone'),
                'guardian_email' => $cell($row, 'guardian_email'),
                'address' => $cell($row, 'address'),
            ]);
            $genderText = $cell($row, 'gender');
            if ($genderText !== '' && self::normalizeGender($genderText) === '') {
                $messages[] = 'Gender "' . $genderText . '" not recognised (use M/F or Male/Female).';
            }
            foreach ($check['errors'] as $field => $message) {
                if ($field !== 'gender') {
                    $messages[] = $message;
                }
            }
            $admission = $check['data']['admission_no'];
            if ($admission !== '') {
                if (isset($seen[$admission])) {
                    $messages[] = 'Admission number ' . $admission . ' appears twice in this file (also row ' . $seen[$admission] . ').';
                }
                $seen[$admission] = $line;
            }
            if ($messages !== []) {
                $errors[] = ['row' => $line, 'name' => $name, 'messages' => array_values(array_unique($messages))];
                continue;
            }
            $candidates[] = ['row' => $line, 'class_id' => $classId, 'department_id' => $departmentId] + $check['data'];
        }

        $remaining = $this->remainingCapacity($schoolId);
        if ($remaining !== null && count($candidates) > $remaining) {
            throw new RuntimeException(sprintf('Your plan allows %d more active student(s), but this file has %d valid row(s). Ask the platform owner to raise your limit, or import fewer students.',
                $remaining, count($candidates)));
        }
        return ['valid' => $candidates, 'errors' => $errors, 'columns' => $columns, 'total' => count($rows)];
    }

    /** @param list<array<string, mixed>> $rows */
    public function queueImport(int $schoolId, int $userId, string $fileName, array $rows): int
    {
        return $this->imports->create($schoolId, $userId, 'students', $fileName, $rows);
    }

    /**
     * Import the next chunk. Safe to call repeatedly and from several tabs: the batch row is locked.
     *
     * @return array{status: string, processed: int, total: int, created: int, skipped: int}
     */
    public function processImportChunk(int $schoolId, int $batchId): array
    {
        $outcome = $this->database->transaction(function () use ($schoolId, $batchId): array {
            $batch = $this->imports->lock($schoolId, $batchId) ?? throw new RuntimeException('Import not found.');
            if (in_array($batch['status'], ['completed', 'cancelled'], true)) {
                return $batch;
            }
            $rows = json_decode((string) $batch['payload'], true) ?: [];
            $start = (int) $batch['processed_rows'];
            $chunk = array_slice($rows, $start, self::IMPORT_CHUNK);
            $credentials = $batch['credentials'] !== null ? json_decode($this->crypto->decrypt((string) $batch['credentials']), true) : [];
            $created = (int) $batch['created_rows'];
            $skipped = (int) $batch['skipped_rows'];
            $remaining = $this->remainingCapacity($schoolId);

            foreach ($chunk as $data) {
                if (($data['admission_no'] !== '' && $this->students->admissionTaken($schoolId, $data['admission_no'])) || ($remaining !== null && $remaining <= 0)) {
                    $skipped++; // Added by someone else meanwhile, or plan limit reached.
                    continue;
                }
                $password = PasswordPolicy::studentTemporary();
                $admission = $data['admission_no'] !== '' ? $data['admission_no'] : $this->nextAdmissionNumber($schoolId);
                $userId = $this->users->create([
                    'school_id' => $schoolId, 'role' => User::ROLE_STUDENT, 'name' => $data['name'], 'username' => $admission,
                    'password_hash' => PasswordPolicy::hash($password), 'must_change_password' => 1,
                ]);
                $this->students->createProfile($schoolId, $userId, ['admission_no' => $admission] + array_diff_key($data, ['row' => 1, 'name' => 1, 'admission_no' => 1]));
                $credentials[] = ['user_id' => $userId, 'name' => $data['name'], 'admission_no' => $admission, 'password' => $password];
                $created++;
                if ($remaining !== null) {
                    $remaining--;
                }
            }
            $processed = $start + count($chunk);
            $done = $processed >= count($rows);
            $this->imports->update($schoolId, $batchId, [
                'status' => $done ? 'completed' : 'processing',
                'processed_rows' => $processed,
                'created_rows' => $created,
                'skipped_rows' => $skipped,
                'credentials' => $credentials === [] ? null : $this->crypto->encrypt(json_encode($credentials, JSON_UNESCAPED_UNICODE)),
            ]);
            return ['status' => $done ? 'completed' : 'processing', 'processed_rows' => $processed, 'total_rows' => count($rows), 'created_rows' => $created, 'skipped_rows' => $skipped,
                'just_completed' => $done];
        });
        if (($outcome['just_completed'] ?? false) === true) {
            $this->audit->record('student.import', sprintf('Imported %d student(s) from a spreadsheet', (int) $outcome['created_rows']), 'import', $batchId, ['skipped' => (int) $outcome['skipped_rows']], $schoolId);
        }
        return [
            'status' => (string) $outcome['status'],
            'processed' => (int) $outcome['processed_rows'],
            'total' => (int) $outcome['total_rows'],
            'created' => (int) $outcome['created_rows'],
            'skipped' => (int) $outcome['skipped_rows'],
        ];
    }

    /** @return list<array{user_id: int, name: string, admission_no: string, password: string}> */
    public function importCredentials(int $schoolId, int $batchId): array
    {
        $batch = $this->imports->find($schoolId, $batchId);
        if ($batch === null || $batch['credentials'] === null) {
            return [];
        }
        $list = json_decode($this->crypto->decrypt((string) $batch['credentials']), true);
        return is_array($list) ? $list : [];
    }

    /** Excel template with the expected columns and two example rows. */
    public function template(int $schoolId): string
    {
        $classes = array_column($this->classes->all($schoolId), 'name');
        return Spreadsheet::xlsx(
            ['Admission No', 'Surname', 'First Name', 'Other Names', 'Class', 'Gender', 'Date of Birth', 'Department', 'Guardian Name', 'Guardian Phone', 'Guardian Email', 'Address'],
            [
                ['', 'Okafor', 'Adaeze', 'Chioma', $classes[0] ?? 'JSS 1 A', 'Female', '2012-03-14', '', 'Mr. Emeka Okafor', '08031234567', 'emeka@example.com', '12 Allen Avenue, Ikeja'],
                ['', 'Bello', 'Ibrahim', '', $classes[4] ?? 'SS 2 A', 'Male', '14/09/2009', 'Science', 'Mrs. Amina Bello', '+2348069876543', '', ''],
            ],
            'Students',
        );
    }

    // ---- Helpers ----------------------------------------------------------------------------

    /** Remaining seats on the school's plan, or null for unlimited. */
    public function remainingCapacity(int $schoolId): ?int
    {
        $school = $this->schools->find($schoolId);
        $max = $school['max_students'] ?? null;
        return $max === null ? null : max(0, (int) $max - $this->students->countActive($schoolId));
    }

    private function assertCapacity(int $schoolId, int $needed): void
    {
        $remaining = $this->remainingCapacity($schoolId);
        if ($remaining !== null && $remaining < $needed) {
            throw new RuntimeException('Your plan’s student limit has been reached. Ask the platform owner to upgrade your plan.');
        }
    }

    /** e.g. "KCL/2026/0042": school initials, current year, next number. */
    private function nextAdmissionNumber(int $schoolId): string
    {
        $school = $this->schools->find($schoolId);
        $code = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) ($school['short_name'] ?? '')) ?? '');
        if ($code === '') {
            $code = initials((string) ($school['name'] ?? 'School'));
        }
        $prefix = substr($code, 0, 4) . '/' . gmdate('Y') . '/';
        $next = $this->students->lastSequence($schoolId, $prefix) + 1;
        do {
            $candidate = $prefix . str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while ($this->students->admissionTaken($schoolId, $candidate));
        return $candidate;
    }

    /** @param list<string> $header @return array<string, int> field => column index */
    private function mapHeaders(array $header): array
    {
        $map = [];
        foreach ($header as $index => $title) {
            $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $title) ?? '');
            foreach (self::HEADER_ALIASES as $field => $aliases) {
                if (!isset($map[$field]) && in_array($key, $aliases, true)) {
                    $map[$field] = $index;
                    break;
                }
            }
        }
        return $map;
    }

    public static function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '234') && strlen($digits) === 13) {
            $digits = '0' . substr($digits, 3);
        } elseif (strlen($digits) === 10 && in_array($digits[0], ['7', '8', '9'], true)) {
            $digits = '0' . $digits; // Excel drops the leading zero of 0803...
        }
        return preg_match('/^0[789][01]\d{8}$/', $digits) ? $digits : null;
    }

    private static function normalizeGender(string $value): string
    {
        return match (strtolower(trim($value))) {
            'f', 'female', 'girl', 'woman' => 'female',
            'm', 'male', 'boy', 'man' => 'male',
            default => '',
        };
    }

    /** "ADAEZE  okafor" → "Adaeze Okafor"; keeps O'Brien / Mc names readable. */
    private static function titleCase(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        return $name === mb_strtoupper($name) || $name === mb_strtolower($name) ? mb_convert_case($name, MB_CASE_TITLE, 'UTF-8') : $name;
    }
}
