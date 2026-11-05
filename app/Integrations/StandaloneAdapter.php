<?php
declare(strict_types=1);

namespace ExamCore\Integrations;

use ExamCore\Contracts\IntegrationInterface;
use ExamCore\Core\Auth;

final class StandaloneAdapter implements IntegrationInterface
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function identity(): ?StudentIdentity
    {
        return $this->auth->identity();
    }

    public function mode(): string
    {
        return 'standalone';
    }
}