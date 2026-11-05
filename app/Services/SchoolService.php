<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Database;
use ExamCore\Models\Plan;
use ExamCore\Models\School;
use ExamCore\Models\User;
use ExamCore\Services\Auth\PasswordPolicy;
use RuntimeException;

/** Owner-side lifecycle of a school (tenant): onboarding, branding, plan, status. */
final class SchoolService
{
    public const NIGERIAN_STATES = ['Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo',
        'Ekiti', 'Enugu', 'FCT Abuja', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun',
        'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'];
    private const LOGO_TYPES = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'];
    private const LOGO_MAX_BYTES = 1_048_576;
    private const TRIAL_DAYS = 30;

    public function __construct(
        private readonly Database $database,
        private readonly School $schools,
        private readonly Plan $plans,
        private readonly User $users,
        private readonly AuditService $audit,
        private readonly string $publicPath,
        private readonly ?SchoolSetupService $setup = null,
    ) {
    }

    /**
     * Validate school + first admin input.
     *
     * @param array<string, mixed> $input
     * @return array<string, string> field => message
     */
    public function validate(array $input, ?int $schoolId = null, bool $withAdmin = true): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        if (mb_strlen($name) < 3 || mb_strlen($name) > 160) {
            $errors['name'] = 'Enter the school name (3–160 characters).';
        }
        $slug = $this->slugify((string) ($input['slug'] ?? '') ?: $name);
        if ($slug === '' || strlen($slug) < 3) {
            $errors['slug'] = 'Use at least 3 letters or numbers for the web address.';
        } elseif (in_array($slug, ['console', 'admin', 'api', 'login', 'logout', 'setup', 'assets', 'uploads', 'school', 'www', 'app'], true)) {
            $errors['slug'] = 'That web address is reserved. Choose another.';
        } elseif ($this->schools->slugExists($slug, $schoolId)) {
            $errors['slug'] = 'Another school already uses this web address.';
        }
        $email = trim((string) ($input['email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid school email or leave it blank.';
        }
        $color = (string) ($input['primary_color'] ?? '#1E40AF');
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $errors['primary_color'] = 'Choose a colour in #RRGGBB format.';
        }
        $state = (string) ($input['state'] ?? '');
        if ($state !== '' && !in_array($state, self::NIGERIAN_STATES, true)) {
            $errors['state'] = 'Choose a state from the list.';
        }
        if ($this->plans->find((int) ($input['plan_id'] ?? 0)) === null) {
            $errors['plan_id'] = 'Choose a plan.';
        }
        $domain = strtolower(trim((string) ($input['custom_domain'] ?? '')));
        if ($domain !== '' && !preg_match('/^(?=.{4,190}$)([a-z0-9](-?[a-z0-9])*\.)+[a-z]{2,}$/', $domain)) {
            $errors['custom_domain'] = 'Enter a domain like cbt.yourschool.ng, without http://.';
        }
        if ($withAdmin) {
            $adminName = trim((string) ($input['admin_name'] ?? ''));
            $adminEmail = trim((string) ($input['admin_email'] ?? ''));
            if (mb_strlen($adminName) < 2 || mb_strlen($adminName) > 120) {
                $errors['admin_name'] = 'Enter the administrator’s full name.';
            }
            if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL) === false) {
                $errors['admin_email'] = 'Enter a valid email for the administrator.';
            }
        }
        return $errors;
    }

    /**
     * @param array<string, mixed> $input validated input
     * @return array{school_id: int, admin_email: string, temporary_password: string}
     */
    public function create(array $input): array
    {
        $plan = $this->plans->find((int) $input['plan_id']) ?? throw new RuntimeException('Plan not found.');
        $temporary = PasswordPolicy::temporary();
        $adminEmail = User::normalizeEmail((string) $input['admin_email']);

        $schoolId = $this->database->transaction(function () use ($input, $plan, $temporary, $adminEmail): int {
            $schoolId = $this->schools->create($this->schoolFields($input) + [
                'status' => $plan['code'] === 'trial' ? 'trial' : 'active',
                'trial_ends_at' => $plan['code'] === 'trial' ? gmdate('Y-m-d', strtotime('+' . self::TRIAL_DAYS . ' days')) : null,
            ]);
            $adminId = $this->users->create([
                'school_id' => $schoolId,
                'role' => User::ROLE_SCHOOL_ADMIN,
                'name' => trim((string) $input['admin_name']),
                'email' => $adminEmail,
                'phone' => trim((string) ($input['admin_phone'] ?? '')) ?: null,
                'password_hash' => PasswordPolicy::hash($temporary),
                'must_change_password' => 1,
            ]);
            // Day-one structure: calendar, departments, JSS/SS classes, subjects, grading.
            $this->setup?->seedDefaults($schoolId);
            $this->audit->record('school.created', 'Created school “' . trim((string) $input['name']) . '” on the ' . $plan['name'] . ' plan', 'school', $schoolId,
                ['admin_id' => $adminId, 'plan' => $plan['code']], $schoolId);
            return $schoolId;
        });

        return ['school_id' => $schoolId, 'admin_email' => $adminEmail, 'temporary_password' => $temporary];
    }

    /** @param array<string, mixed> $input validated input */
    public function update(int $schoolId, array $input): void
    {
        $before = $this->schools->find($schoolId) ?? throw new RuntimeException('School not found.');
        $fields = $this->schoolFields($input);
        $fields['notes'] = trim((string) ($input['notes'] ?? '')) ?: null;
        $override = trim((string) ($input['max_students_override'] ?? ''));
        $fields['max_students_override'] = $override === '' ? null : max(1, (int) $override);
        $this->schools->update($schoolId, $fields);
        $changed = array_keys(array_filter($fields, static fn ($value, $key): bool => (string) ($before[$key] ?? '') !== (string) ($value ?? ''), ARRAY_FILTER_USE_BOTH));
        $this->audit->record('school.updated', 'Updated school details' . ($changed === [] ? '' : ': ' . implode(', ', $changed)), 'school', $schoolId, ['fields' => $changed], $schoolId);
    }

    public function changeStatus(int $schoolId, string $status, string $reason = ''): void
    {
        if (!in_array($status, School::STATUSES, true)) {
            throw new \InvalidArgumentException('Unknown status.');
        }
        $school = $this->schools->find($schoolId) ?? throw new RuntimeException('School not found.');
        $this->schools->update($schoolId, ['status' => $status]);
        $this->audit->record('school.status', sprintf('Changed status from %s to %s', $school['status'], $status) . ($reason !== '' ? ' — ' . $reason : ''),
            'school', $schoolId, ['from' => $school['status'], 'to' => $status, 'reason' => $reason], $schoolId);
    }

    /** @return array{email: string, temporary_password: string} */
    public function resetAdminPassword(int $schoolId): array
    {
        $admin = $this->users->primaryAdmin($schoolId) ?? throw new RuntimeException('This school has no administrator account.');
        $temporary = PasswordPolicy::temporary();
        $this->users->update((int) $admin['id'], ['password_hash' => PasswordPolicy::hash($temporary), 'must_change_password' => 1, 'failed_logins' => 0, 'locked_until' => null]);
        $this->audit->record('school.admin_password_reset', 'Reset the administrator password for ' . $admin['email'], 'user', (int) $admin['id'], [], $schoolId);
        return ['email' => (string) $admin['email'], 'temporary_password' => $temporary];
    }

    /**
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file
     * @return string|null error message
     */
    public function uploadLogo(int $schoolId, array $file): ?string
    {
        if ($file['size'] > self::LOGO_MAX_BYTES) {
            return 'The logo must be 1 MB or smaller.';
        }
        // Detect the real type from the bytes; the browser-supplied name and MIME are untrusted.
        $info = @getimagesize($file['tmp_name']);
        $extension = is_array($info) ? (self::LOGO_TYPES[$info[2]] ?? null) : null;
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ($extension === null || !in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            return 'Upload a PNG, JPG or WebP image. (SVG is not accepted for security reasons.)';
        }
        if ($info[0] < 32 || $info[1] < 32 || $info[0] > 4000 || $info[1] > 4000) {
            return 'The logo should be between 32 and 4000 pixels on each side.';
        }
        $directory = $this->publicPath . '/uploads/schools/' . $schoolId;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return 'The logo could not be saved (storage is not writable).';
        }
        $relative = 'uploads/schools/' . $schoolId . '/logo-' . bin2hex(random_bytes(6)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $this->publicPath . '/' . $relative)) {
            return 'The logo could not be saved.';
        }
        $old = $this->schools->find($schoolId)['logo_path'] ?? null;
        $this->schools->update($schoolId, ['logo_path' => $relative]);
        if (is_string($old) && str_starts_with($old, 'uploads/schools/' . $schoolId . '/')) {
            @unlink($this->publicPath . '/' . $old);
        }
        $this->audit->record('school.logo', 'Updated the school logo', 'school', $schoolId, [], $schoolId);
        return null;
    }

    /** Scheduler: trials past their end date are suspended until the school subscribes. */
    public function suspendExpiredTrials(): int
    {
        $count = 0;
        foreach ($this->schools->expiredTrialIds(gmdate('Y-m-d')) as $schoolId) {
            $this->schools->update($schoolId, ['status' => 'suspended']);
            $this->audit->record('school.trial_expired', 'Trial ended; school suspended until it subscribes', 'school', $schoolId, [], $schoolId);
            $count++;
        }
        return $count;
    }

    public function slugify(string $value): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value), '-'));
        return substr($slug, 0, 60);
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private function schoolFields(array $input): array
    {
        $optional = static fn (string $key): ?string => ($value = trim((string) ($input[$key] ?? ''))) === '' ? null : $value;
        return [
            'name' => trim((string) $input['name']),
            'slug' => $this->slugify((string) ($input['slug'] ?? '') ?: (string) $input['name']),
            'short_name' => $optional('short_name'),
            'motto' => $optional('motto'),
            'plan_id' => (int) $input['plan_id'],
            'email' => ($email = $optional('email')) === null ? null : strtolower($email),
            'phone' => $optional('phone'),
            'address' => $optional('address'),
            'state' => $optional('state'),
            'primary_color' => strtoupper((string) ($input['primary_color'] ?? '#1E40AF')),
            'custom_domain' => ($domain = $optional('custom_domain')) === null ? null : strtolower($domain),
        ];
    }
}
