<?php
declare(strict_types=1);

namespace ExamCore\Integrations;

use Closure;
use ExamCore\Contracts\IntegrationInterface;

final class WebsiteAdapter implements IntegrationInterface
{
    /** Provider must obtain and map identity from trusted server-side host state. */
    public function __construct(private readonly Closure $identityProvider)
    {
    }

    public function identity(): ?StudentIdentity
    {
        $identity = ($this->identityProvider)();
        return $identity instanceof StudentIdentity ? $identity : null;
    }

    public function mode(): string
    {
        return 'website';
    }
}