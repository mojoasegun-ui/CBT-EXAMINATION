<?php
declare(strict_types=1);

namespace ExamCore\Core\Exceptions;

use RuntimeException;
use Throwable;

/**
 * An expected, user-facing HTTP failure (404, 403, 419, 429 ...).
 *
 * The message is safe to show to end users. Anything that is not an
 * HttpException is treated as an unexpected server error and hidden.
 */
class HttpException extends RuntimeException
{
    /** @param array<string, string> $headers */
    public function __construct(
        private readonly int $status,
        string $message = '',
        private readonly array $headers = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message !== '' ? $message : self::defaultMessage($status), 0, $previous);
    }

    public static function notFound(string $message = ''): self
    {
        return new self(404, $message);
    }

    public static function forbidden(string $message = ''): self
    {
        return new self(403, $message);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public static function defaultMessage(int $status): string
    {
        return match ($status) {
            400 => 'The request could not be understood.',
            403 => 'You do not have permission to view this page.',
            404 => 'The page you are looking for could not be found.',
            405 => 'This action is not allowed here.',
            419 => 'Your session expired. Refresh the page and try again.',
            422 => 'Some of the information provided is invalid.',
            429 => 'Too many requests. Please wait a moment and try again.',
            503 => 'The service is temporarily unavailable. Please try again shortly.',
            default => 'Something went wrong on our side. Please try again.',
        };
    }
}
