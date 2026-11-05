<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Container;

final class SettingsService
{
    public function __construct(private readonly Container $container)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->container->get('config');
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}