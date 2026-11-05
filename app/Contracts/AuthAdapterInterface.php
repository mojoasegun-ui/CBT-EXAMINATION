<?php
declare(strict_types=1);

namespace ExamCore\Contracts;

use ExamCore\Integrations\StudentIdentity;

interface AuthAdapterInterface
{
    public function identity(): ?StudentIdentity;
}