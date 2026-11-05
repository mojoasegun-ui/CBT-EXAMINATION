<?php
declare(strict_types=1);

namespace ExamCore\Core\Queue;

interface JobHandler
{
    /**
     * Throw to signal failure; the worker retries with backoff until max attempts.
     *
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload): void;
}
