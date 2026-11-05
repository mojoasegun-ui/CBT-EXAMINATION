<?php
declare(strict_types=1);

namespace ExamCore\Core\Queue;

use ExamCore\Core\Container;
use ExamCore\Core\Logger;
use Throwable;

final class Worker
{
    /** @param array<string, string> $handlers job type => container service id */
    public function __construct(
        private readonly Queue $queue,
        private readonly Container $container,
        private readonly Logger $logger,
        private readonly array $handlers,
    ) {
    }

    /** Process jobs until the queue is empty or a budget is spent. Returns the number processed. */
    public function work(int $maxJobs, float $maxSeconds): int
    {
        $deadline = microtime(true) + $maxSeconds;
        $processed = 0;
        while ($processed < $maxJobs && microtime(true) < $deadline) {
            $job = $this->queue->reserve();
            if ($job === null) {
                break;
            }
            $processed++;
            $this->run($job);
        }
        return $processed;
    }

    /** @param array{id: int, type: string, payload: array<string, mixed>, attempts: int, max_attempts: int} $job */
    private function run(array $job): void
    {
        try {
            $serviceId = $this->handlers[$job['type']] ?? null;
            if ($serviceId === null) {
                throw new \RuntimeException('No handler is registered for job type "' . $job['type'] . '".');
            }
            $handler = $this->container->get($serviceId);
            if (!$handler instanceof JobHandler) {
                throw new \RuntimeException('Handler for "' . $job['type'] . '" must implement JobHandler.');
            }
            $handler->handle($job['payload']);
            $this->queue->complete($job['id']);
        } catch (Throwable $exception) {
            $error = $exception::class . ': ' . $exception->getMessage();
            if ($job['attempts'] >= $job['max_attempts']) {
                $this->queue->fail($job['id'], $error);
                $this->logger->log('error', 'Job failed permanently.', ['job_id' => $job['id'], 'type' => $job['type'], 'error' => $error]);
                return;
            }
            // Exponential backoff: 1, 4, 9 ... minutes.
            $this->queue->retry($job['id'], 60 * $job['attempts'] ** 2, $error);
            $this->logger->log('warning', 'Job failed; will retry.', ['job_id' => $job['id'], 'type' => $job['type'], 'attempt' => $job['attempts'], 'error' => $error]);
        }
    }
}
