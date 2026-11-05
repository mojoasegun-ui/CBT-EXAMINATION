<?php
declare(strict_types=1);

namespace ExamCore\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Service container with explicit factories plus reflection autowiring.
 *
 * Classes that are not registered are built from their constructor's type hints,
 * so controllers and services need no manual wiring. Scalars (strings, ints, config
 * values) cannot be guessed: give those classes an explicit factory with set().
 */
final class Container
{
    /** @var array<string, Closure(self): mixed> */
    private array $factories = [];
    /** @var array<string, mixed> */
    private array $instances = [];
    /** @var array<string, true> */
    private array $resolving = [];

    public function __construct()
    {
        $this->instances[self::class] = $this;
    }

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = Closure::fromCallable($factory);
        unset($this->instances[$id]);
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
        unset($this->factories[$id]);
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances) || isset($this->factories[$id]);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }
        if (isset($this->resolving[$id])) {
            throw new RuntimeException('Circular service dependency detected: ' . $id);
        }

        $this->resolving[$id] = true;
        try {
            if (isset($this->factories[$id])) {
                return $this->instances[$id] = ($this->factories[$id])($this);
            }
            if (class_exists($id)) {
                return $this->instances[$id] = $this->autowire($id);
            }
            throw new RuntimeException('Service is not registered: ' . $id);
        } finally {
            unset($this->resolving[$id]);
        }
    }

    /** @param class-string $class */
    private function autowire(string $class): object
    {
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException('Cannot autowire non-instantiable class: ' . $class);
        }
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }
        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $type instanceof ReflectionNamedType && !$type->isBuiltin() ? $type->getName() : null;
            if ($name !== null && ($this->has($name) || class_exists($name))) {
                $arguments[] = $this->get($name);
                continue;
            }
            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }
            if ($name !== null && $parameter->allowsNull()) {
                $arguments[] = null; // Unbound interface that is optional by design.
                continue;
            }
            throw new RuntimeException(sprintf('Cannot autowire $%s of %s; register a factory for it.', $parameter->getName(), $class));
        }
        return $reflection->newInstanceArgs($arguments);
    }
}
