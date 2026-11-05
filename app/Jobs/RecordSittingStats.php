<?php
declare(strict_types=1);

namespace ExamCore\Jobs;

use ExamCore\Core\Queue\JobHandler;
use ExamCore\Models\ExamSitting;

/** After a sitting is marked: update how often each question was answered / answered correctly. */
final class RecordSittingStats implements JobHandler
{
    public function __construct(private readonly ExamSitting $sittings)
    {
    }

    public function handle(array $payload): void
    {
        $id = filter_var($payload['sitting_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id !== false && $id > 0) {
            $this->sittings->recordQuestionStats($id);
        }
    }
}
