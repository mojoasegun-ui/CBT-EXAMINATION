<?php
declare(strict_types=1);

namespace ExamCore\Services\Exceptions;

use DomainException;

/**
 * Expected business-rule failures in the attempt lifecycle. Messages are safe to show to students.
 *
 * Deliberately NOT a RuntimeException: PDOException extends RuntimeException, and the old
 * `catch (RuntimeException)` blocks silently turned database outages into "not found".
 */
final class AttemptException extends DomainException
{
    public const NOT_FOUND = 'not_found';
    public const UNAVAILABLE = 'unavailable';
    public const ALREADY_SUBMITTED = 'already_submitted';
    public const INVALID_ANSWER = 'invalid_answer';
    public const TOO_MANY_PAPERS = 'too_many_papers';

    private function __construct(private readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function notFound(): self
    {
        return new self(self::NOT_FOUND, 'This exam attempt could not be found. It may belong to another browser session.');
    }

    public static function unavailable(): self
    {
        return new self(self::UNAVAILABLE, 'That exam is not available right now. Please choose another one.');
    }

    public static function alreadySubmitted(): self
    {
        return new self(self::ALREADY_SUBMITTED, 'This exam has already been submitted.');
    }

    public static function invalidAnswer(): self
    {
        return new self(self::INVALID_ANSWER, 'That answer does not belong to this exam.');
    }

    public static function tooManyPapers(int $max): self
    {
        return new self(self::TOO_MANY_PAPERS, sprintf('You can combine at most %d subjects in one sitting.', $max));
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
