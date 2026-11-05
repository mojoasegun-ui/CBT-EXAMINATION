<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Logger;
use ExamCore\Models\AuditLog;
use Throwable;

/** Records who did what, to which record, from where. Shown in the Owner Console and school admin. */
final class AuditService
{
    public function __construct(private readonly AuditLog $logs, private readonly Guard $guard, private readonly Logger $logger)
    {
    }

    /** @param array<string, scalar|null|array<array-key, scalar|null>> $meta */
    public function record(string $action, string $summary, ?string $targetType = null, ?int $targetId = null, array $meta = [], ?int $schoolId = null, ?array $actor = null): void
    {
        $actor ??= $this->guard->user();
        try {
            $this->logs->insert([
                'school_id' => $schoolId ?? ($actor['school_id'] ?? null),
                'actor_id' => $actor['id'] ?? null,
                'actor_name' => $actor['name'] ?? null,
                'impersonator_id' => $this->guard->impersonatorId(),
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'summary' => $summary,
                'meta' => $meta,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Throwable $exception) {
            // Never block the user's action because the audit write failed, but make it visible.
            $this->logger->log('error', 'Audit log write failed.', ['action' => $action, 'exception' => $exception::class, 'message' => $exception->getMessage()]);
        }
    }
}
