<?php
declare(strict_types=1);

namespace ExamCore\Core;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Models\School;

/**
 * The school (tenant) the current request acts for.
 *
 * Every school-owned query must take its school id from here, never from user input,
 * so one school can never read or change another school's data.
 */
final class TenantContext
{
    /** @var array<string, mixed>|null */
    private ?array $school = null;

    public function __construct(private readonly Guard $guard, private readonly School $schools)
    {
    }

    public function id(): int
    {
        return (int) $this->school()['id'];
    }

    /** @return array<string, mixed> */
    public function school(): array
    {
        if ($this->school !== null) {
            return $this->school;
        }
        $schoolId = $this->guard->schoolId();
        $school = $schoolId === null ? null : $this->schools->find($schoolId);
        if ($school === null) {
            throw HttpException::forbidden('This area is only available to school accounts.');
        }
        return $this->school = $school;
    }

    /** @return list<string> feature codes granted by the school's plan */
    public function features(): array
    {
        $features = json_decode((string) ($this->school()['plan_features'] ?? '[]'), true);
        return is_array($features) ? $features : [];
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features(), true);
    }
}
