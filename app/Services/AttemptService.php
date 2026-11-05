<?php
declare(strict_types=1);

namespace ExamCore\Services;

use DateTimeImmutable;
use DateTimeZone;
use ExamCore\Core\Database;
use ExamCore\Core\Queue\Queue;
use ExamCore\Models\Attempt;
use ExamCore\Models\AttemptAnswer;
use ExamCore\Models\Exam;
use ExamCore\Models\Question;
use ExamCore\Services\Exceptions\AttemptException;

/**
 * Attempt lifecycle: start → save answers / flags → submit (or auto-expire) → review.
 *
 * An attempt's question set is frozen at start in `question_order`. Every later step
 * (validation, grading, review) reads that list, which is what makes multi-subject
 * sittings work and keeps attempts stable if a paper is edited afterwards.
 */
final class AttemptService
{
    public const MAX_PAPERS_PER_SITTING = 4; // JAMB UTME: Use of English + 3 subjects.
    public const JOB_RESULT_EMAIL = 'mail.attempt_result';

    public function __construct(
        private readonly Database $database,
        private readonly Exam $exams,
        private readonly Question $questions,
        private readonly Attempt $attempts,
        private readonly AttemptAnswer $answers,
        private readonly ?Queue $queue = null,
        private readonly float $passMarkPercent = 50.0,
    ) {
    }

    /**
     * @param list<int> $selectedExamIds additional papers for a multi-subject sitting (the primary is always included)
     * @return array{attempt_id: int, exam_ids: list<int>, question_count: int, duration_minutes: int}
     * @throws AttemptException
     */
    public function startAttempt(
        int $examId,
        string $sessionToken,
        ?string $studentName = null,
        ?string $studentEmail = null,
        ?string $accessToken = null,
        array $selectedExamIds = [],
    ): array {
        $examIds = $this->normalizeExamIds($examId, $selectedExamIds);
        if (count($examIds) > self::MAX_PAPERS_PER_SITTING) {
            throw AttemptException::tooManyPapers(self::MAX_PAPERS_PER_SITTING);
        }

        $questionOrder = [];
        $total = 0.0;
        $duration = 0;
        foreach ($examIds as $id) {
            $exam = $this->exams->findAvailable($id);
            if ($exam === null) {
                throw AttemptException::unavailable();
            }
            $paper = $this->questions->listForExam($id);
            if ($paper === [] || count($paper) !== (int) $exam['total_questions']) {
                throw AttemptException::unavailable();
            }
            // Shuffle within each paper but keep papers grouped, ready for per-subject tabs.
            $ids = array_map(static fn (array $question): int => (int) $question['id'], $paper);
            shuffle($ids);
            array_push($questionOrder, ...$ids);
            $total += array_sum(array_map(static fn (array $question): float => (float) $question['marks'], $paper));
            $duration += (int) $exam['duration_minutes'];
        }

        $attemptId = $this->database->transaction(fn (): int => $this->attempts->create(
            examId: $examIds[0],
            sessionToken: $sessionToken,
            startedAt: gmdate('Y-m-d H:i:s'),
            total: $total,
            studentName: $studentName,
            studentEmail: $studentEmail,
            accessToken: $accessToken,
            multiExamJson: count($examIds) > 1 ? json_encode($examIds, JSON_THROW_ON_ERROR) : null,
            questionOrderJson: json_encode($questionOrder, JSON_THROW_ON_ERROR),
            durationMinutes: $duration,
        ));

        return ['attempt_id' => $attemptId, 'exam_ids' => $examIds, 'question_count' => count($questionOrder), 'duration_minutes' => $duration];
    }

    /** @return array<string, mixed> @throws AttemptException */
    public function findOwned(int $attemptId, string $sessionToken): array
    {
        return $this->attempts->findOwned($attemptId, $sessionToken) ?? throw AttemptException::notFound();
    }

    /**
     * Everything the exam runner needs. Questions use the public projection (no answers).
     *
     * @return array{attempt: array<string, mixed>, questions: list<array<string, mixed>>, answers: array<int, string>, flagged_questions: list<int>, remaining_seconds: int}
     * @throws AttemptException
     */
    public function getRunnerData(int $attemptId, string $sessionToken): array
    {
        $attempt = $this->findOwned($attemptId, $sessionToken);
        return [
            'attempt' => $attempt,
            'questions' => $this->questions->listByIds($this->questionIds($attempt)),
            'answers' => $this->answers->selectedOptions($attemptId),
            'flagged_questions' => $this->answers->flaggedQuestions($attemptId),
            'remaining_seconds' => $this->remainingSeconds($attempt),
        ];
    }

    /** @return array{saved: bool, expired: bool} @throws AttemptException */
    public function saveAnswer(int $attemptId, int $questionId, string $option, string $sessionToken): array
    {
        if (!in_array($option, ['A', 'B', 'C', 'D'], true)) {
            throw AttemptException::invalidAnswer();
        }
        return $this->withOpenAttempt($attemptId, $sessionToken, $questionId, function () use ($attemptId, $questionId, $option): void {
            $this->answers->save($attemptId, $questionId, $option);
        });
    }

    /** @return array{saved: bool, expired: bool} @throws AttemptException */
    public function setFlaggedQuestion(int $attemptId, int $questionId, bool $flagged, string $sessionToken): array
    {
        return $this->withOpenAttempt($attemptId, $sessionToken, $questionId, function () use ($attemptId, $questionId, $flagged): void {
            $this->answers->setFlagged($attemptId, $questionId, $flagged);
        });
    }

    /**
     * Grade and close the attempt. Idempotent: submitting twice returns the stored result.
     *
     * @return array<string, mixed> the attempt row after submission
     * @throws AttemptException
     */
    public function submitAttempt(int $attemptId, string $sessionToken): array
    {
        return $this->database->transaction(function () use ($attemptId, $sessionToken): array {
            $attempt = $this->attempts->findOwned($attemptId, $sessionToken, true) ?? throw AttemptException::notFound();
            if ($attempt['status'] === 'in_progress') {
                $this->finalize($attempt);
            }
            return $this->attempts->findOwned($attemptId, $sessionToken) ?? $attempt;
        });
    }

    /**
     * Auto-submit attempts whose time ran out while the student was away (closed tab, dead
     * battery, lost data). Run by the scheduler. Returns how many were finalized.
     */
    public function expireOverdue(int $graceSeconds = 120, int $limit = 100): int
    {
        $expired = 0;
        foreach ($this->attempts->listOverdueIds($graceSeconds, $limit) as $attemptId) {
            $expired += (int) $this->database->transaction(function () use ($attemptId): bool {
                $attempt = $this->attempts->findById($attemptId, true);
                if ($attempt === null || $attempt['status'] !== 'in_progress' || $this->remainingSeconds($attempt) > 0) {
                    return false;
                }
                $this->finalize($attempt);
                return true;
            });
        }
        return $expired;
    }

    /**
     * @return array{attempt: array<string, mixed>, questions: list<array<string, mixed>>, pass_mark_percent: float}
     * @throws AttemptException
     */
    public function getReview(int $attemptId, string $sessionToken): array
    {
        $attempt = $this->attempts->findOwned($attemptId, $sessionToken);
        if ($attempt === null || $attempt['status'] === 'in_progress') {
            throw AttemptException::notFound();
        }
        return $this->review($attempt);
    }

    /**
     * Trusted variant for queued jobs, which run without a browser session.
     *
     * @return array{attempt: array<string, mixed>, questions: list<array<string, mixed>>, pass_mark_percent: float}|null
     */
    public function getReviewForSystem(int $attemptId): ?array
    {
        $attempt = $this->attempts->findById($attemptId);
        return $attempt === null || $attempt['status'] === 'in_progress' ? null : $this->review($attempt);
    }

    /** @param array<string, mixed> $attempt */
    public function remainingSeconds(array $attempt): int
    {
        $startedAt = new DateTimeImmutable((string) $attempt['started_at'], new DateTimeZone('UTC'));
        $expiresAt = $startedAt->getTimestamp() + ((int) $attempt['duration_minutes'] * 60);
        return max(0, $expiresAt - time());
    }

    /**
     * Exam ids that make up this attempt (the primary first).
     *
     * @param array<string, mixed> $attempt
     * @return list<int>
     */
    public function examIds(array $attempt): array
    {
        $ids = $this->decodeIds($attempt['multi_exam'] ?? null);
        return $ids === [] ? [(int) $attempt['exam_id']] : $ids;
    }

    public function passMarkPercent(): float
    {
        return $this->passMarkPercent;
    }

    /**
     * Shared guard for autosave-style writes: lock the attempt, check it is open, in time,
     * and that the question belongs to it.
     *
     * @return array{saved: bool, expired: bool}
     */
    private function withOpenAttempt(int $attemptId, string $sessionToken, int $questionId, callable $write): array
    {
        return $this->database->transaction(function () use ($attemptId, $sessionToken, $questionId, $write): array {
            $attempt = $this->attempts->findOwned($attemptId, $sessionToken, true) ?? throw AttemptException::notFound();
            if ($attempt['status'] !== 'in_progress') {
                throw AttemptException::alreadySubmitted();
            }
            if ($this->remainingSeconds($attempt) <= 0) {
                return ['saved' => false, 'expired' => true];
            }
            if (!in_array($questionId, $this->questionIds($attempt), true)) {
                throw AttemptException::invalidAnswer();
            }
            $write();
            return ['saved' => true, 'expired' => false];
        });
    }

    /**
     * Grade every question in the attempt and close it. Caller must hold the row lock.
     *
     * @param array<string, mixed> $attempt
     */
    private function finalize(array $attempt): void
    {
        $attemptId = (int) $attempt['id'];
        $questions = $this->questions->listForGradingByIds($this->questionIds($attempt));
        $selected = $this->answers->selectedOptions($attemptId);
        $score = 0.0;
        $total = 0.0;
        $results = [];
        foreach ($questions as $question) {
            $questionId = (int) $question['id'];
            $correct = ($selected[$questionId] ?? null) === $question['correct_option'];
            $marks = (float) $question['marks'];
            $total += $marks;
            if ($correct) {
                $score += $marks;
            }
            $results[$questionId] = $correct;
        }
        $this->answers->markGradedBulk($attemptId, $results);
        $status = $this->remainingSeconds($attempt) <= 0 ? 'timed_out' : 'submitted';
        $this->attempts->submit($attemptId, $score, $total, gmdate('Y-m-d H:i:s'), $status);

        // Queued inside the same transaction: the email job exists if and only if the result does.
        if ($this->queue !== null && trim((string) ($attempt['student_email'] ?? '')) !== '') {
            $this->queue->push(self::JOB_RESULT_EMAIL, ['attempt_id' => $attemptId]);
        }
    }

    /**
     * @param array<string, mixed> $attempt
     * @return array{attempt: array<string, mixed>, questions: list<array<string, mixed>>, pass_mark_percent: float}
     */
    private function review(array $attempt): array
    {
        $questions = $this->questions->listForGradingByIds($this->questionIds($attempt));
        return [
            'attempt' => $attempt,
            'questions' => $this->answers->review((int) $attempt['id'], $questions),
            'pass_mark_percent' => $this->passMarkPercent,
        ];
    }

    /**
     * The frozen question set. Attempts created before question_order existed fall back to the paper.
     *
     * @param array<string, mixed> $attempt
     * @return list<int>
     */
    private function questionIds(array $attempt): array
    {
        $ids = $this->decodeIds($attempt['question_order'] ?? null);
        if ($ids !== []) {
            return $ids;
        }
        return array_map(static fn (array $question): int => (int) $question['id'], $this->questions->listForExam((int) $attempt['exam_id']));
    }

    /**
     * Primary first, then the others in the order chosen, without duplicates.
     *
     * @param list<int|string> $selectedExamIds
     * @return list<int>
     */
    private function normalizeExamIds(int $examId, array $selectedExamIds): array
    {
        $ids = [$examId];
        foreach ($selectedExamIds as $value) {
            $id = filter_var($value, FILTER_VALIDATE_INT);
            if ($id !== false && $id > 0) {
                $ids[] = (int) $id;
            }
        }
        return array_values(array_unique($ids));
    }

    /** @return list<int> */
    private function decodeIds(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }
        $ids = [];
        foreach ($decoded as $item) {
            $id = filter_var($item, FILTER_VALIDATE_INT);
            if ($id !== false && $id > 0) {
                $ids[] = (int) $id;
            }
        }
        return array_values(array_unique($ids));
    }
}
