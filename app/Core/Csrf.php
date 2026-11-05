<?php
declare(strict_types=1);

namespace ExamCore\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    public function verify(?string $candidate): bool
    {
        $expected = $this->session->get(self::SESSION_KEY);
        return is_string($candidate) && is_string($expected) && hash_equals($expected, $candidate);
    }

    public function rotate(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set(self::SESSION_KEY, $token);
        return $token;
    }
}