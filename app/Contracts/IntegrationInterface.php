<?php
declare(strict_types=1);

namespace ExamCore\Contracts;

interface IntegrationInterface extends AuthAdapterInterface
{
    public function mode(): string;
}