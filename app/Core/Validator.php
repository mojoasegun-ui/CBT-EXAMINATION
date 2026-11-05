<?php
declare(strict_types=1);

namespace ExamCore\Core;

use InvalidArgumentException;

final class Validator
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    /** @param array<string, mixed> $data @param array<string, string> $rules @return array<string, mixed>|null */
    public function validate(array $data, array $rules): ?array
    {
        $this->errors = [];
        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            foreach (explode('|', $ruleString) as $rule) {
                [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);
                $message = $this->failure($name, $argument, $value);
                if ($message !== null) {
                    $this->errors[$field][] = $message;
                    if ($name === 'required') {
                        break;
                    }
                }
            }
        }
        return $this->errors === [] ? $data : null;
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    private function failure(string $rule, ?string $argument, mixed $value): ?string
    {
        $present = $value !== null && $value !== '';
        $valid = match ($rule) {
            'required' => $present,
            'email' => !$present || (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false),
            'string' => !$present || is_string($value),
            'integer' => !$present || filter_var($value, FILTER_VALIDATE_INT) !== false,
            'min' => !$present || (is_string($value) && $this->length($value) >= (int) $argument),
            'max' => !$present || (is_string($value) && $this->length($value) <= (int) $argument),
            'boolean' => !$present || in_array($value, [true, false, 0, 1, '0', '1'], true),
            default => throw new InvalidArgumentException('Unknown validation rule.'),
        };
        return $valid ? null : 'The field is invalid for the ' . $rule . ' rule.';
    }

    private function length(string $value): int
    {
        $count = preg_match_all('/./us', $value);
        return $count === false ? strlen($value) : $count;
    }
}