<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Database;
use ExamCore\Models\Teacher;
use ExamCore\Models\User;
use ExamCore\Services\Auth\PasswordPolicy;
use RuntimeException;

/** Staff accounts inside a school: teachers, reviewers and extra administrators. */
final class TeacherService
{
    public const ROLES = [User::ROLE_TEACHER => 'Teacher', User::ROLE_REVIEWER => 'Reviewer (approves questions)', User::ROLE_SCHOOL_ADMIN => 'Administrator'];
    public const TITLES = ['Mr.', 'Mrs.', 'Miss', 'Ms.', 'Dr.', 'Prof.', 'Engr.', 'Rev.'];

    public function __construct(
        private readonly Database $database,
        private readonly Teacher $teachers,
        private readonly User $users,
        private readonly AuditService $audit,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{errors: array<string, string>, data: array<string, mixed>}
     */
    public function validate(int $schoolId, array $input, int $exceptUserId = 0): array
    {
        $errors = [];
        $name = trim(preg_replace('/\s+/', ' ', (string) ($input['name'] ?? '')) ?? '');
        if (mb_strlen($name) < 3 || mb_strlen($name) > 120) {
            $errors['name'] = 'Enter the full name.';
        }
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email; it is used to sign in.';
        } elseif ($this->teachers->emailTaken($schoolId, $email, $exceptUserId)) {
            $errors['email'] = 'Someone at your school already uses this email.';
        }
        $role = (string) ($input['role'] ?? User::ROLE_TEACHER);
        if (!array_key_exists($role, self::ROLES)) {
            $errors['role'] = 'Choose a role.';
        }
        $phone = trim((string) ($input['phone'] ?? ''));
        if ($phone !== '' && StudentService::normalizePhone($phone) === null) {
            $errors['phone'] = 'Enter a Nigerian number like 0803 123 4567.';
        }
        $staffNo = strtoupper(trim((string) ($input['staff_no'] ?? '')));
        if ($staffNo !== '' && $this->teachers->staffNoTaken($schoolId, $staffNo, $exceptUserId)) {
            $errors['staff_no'] = 'Another staff member has this staff number.';
        }
        $title = (string) ($input['title'] ?? '');
        return ['errors' => $errors, 'data' => [
            'name' => $name, 'email' => $email, 'role' => $role, 'phone' => $phone === '' ? null : StudentService::normalizePhone($phone),
            'staff_no' => $staffNo === '' ? null : $staffNo, 'title' => in_array($title, self::TITLES, true) ? $title : null,
            'qualification' => ($q = trim((string) ($input['qualification'] ?? ''))) === '' ? null : mb_substr($q, 0, 120),
        ]];
    }

    /** @param array<string, mixed> $data validated @return array{user_id: int, password: string} */
    public function create(int $schoolId, array $data): array
    {
        $password = PasswordPolicy::temporary();
        $userId = $this->database->transaction(function () use ($schoolId, $data, $password): int {
            $userId = $this->users->create([
                'school_id' => $schoolId, 'role' => $data['role'], 'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
                'password_hash' => PasswordPolicy::hash($password), 'must_change_password' => 1,
            ]);
            $this->teachers->saveProfile($schoolId, $userId, $data['title'], $data['staff_no'], $data['qualification']);
            return $userId;
        });
        $this->audit->record('staff.created', 'Added ' . $data['name'] . ' as ' . strtolower(explode(' ', self::ROLES[$data['role']])[0]), 'user', $userId, [], $schoolId);
        return ['user_id' => $userId, 'password' => $password];
    }

    /** @param array<string, mixed> $data validated */
    public function update(int $schoolId, int $userId, array $data, int $actorId): void
    {
        $current = $this->teachers->find($schoolId, $userId) ?? throw new RuntimeException('Staff member not found.');
        if ($userId === $actorId && $data['role'] !== User::ROLE_SCHOOL_ADMIN) {
            throw new RuntimeException('You cannot remove your own administrator role.');
        }
        $this->database->transaction(function () use ($schoolId, $userId, $data): void {
            $this->users->update($userId, ['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone']]);
            $this->users->setRole($schoolId, $userId, $data['role']);
            $this->teachers->saveProfile($schoolId, $userId, $data['title'], $data['staff_no'], $data['qualification']);
        });
        $this->audit->record('staff.updated', 'Updated ' . $current['name'] . '’s details', 'user', $userId, [], $schoolId);
    }

    public function resetPassword(int $schoolId, int $userId): string
    {
        $staff = $this->teachers->find($schoolId, $userId) ?? throw new RuntimeException('Staff member not found.');
        $password = PasswordPolicy::temporary();
        $this->users->update($userId, ['password_hash' => PasswordPolicy::hash($password), 'must_change_password' => 1, 'failed_logins' => 0, 'locked_until' => null]);
        $this->audit->record('staff.password_reset', 'Reset the password for ' . $staff['name'], 'user', $userId, [], $schoolId);
        return $password;
    }

    public function setActive(int $schoolId, int $userId, bool $active, int $actorId): void
    {
        $staff = $this->teachers->find($schoolId, $userId) ?? throw new RuntimeException('Staff member not found.');
        if ($userId === $actorId) {
            throw new RuntimeException('You cannot deactivate your own account.');
        }
        $this->users->update($userId, ['status' => $active ? 'active' : 'disabled']);
        $this->audit->record($active ? 'staff.activated' : 'staff.deactivated', ($active ? 'Reactivated ' : 'Deactivated ') . $staff['name'], 'user', $userId, [], $schoolId);
    }
}
