<?php
declare(strict_types=1);

namespace ExamCore\Jobs;

use ExamCore\Core\Queue\JobHandler;
use ExamCore\Services\AttemptService;
use ExamCore\Services\EmailService;

final class SendAttemptResultEmail implements JobHandler
{
    public function __construct(private readonly AttemptService $attempts, private readonly EmailService $email)
    {
    }

    public function handle(array $payload): void
    {
        $attemptId = filter_var($payload['attempt_id'] ?? null, FILTER_VALIDATE_INT);
        if ($attemptId === false || $attemptId < 1) {
            return; // Malformed payload: nothing to retry.
        }
        $review = $this->attempts->getReviewForSystem($attemptId);
        if ($review === null) {
            return; // Attempt deleted or reopened: nothing to send.
        }
        $this->email->sendAttemptResults($review['attempt'], $review['questions']);
    }
}
