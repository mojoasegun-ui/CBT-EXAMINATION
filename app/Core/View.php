<?php
declare(strict_types=1);

namespace ExamCore\Core;

use RuntimeException;

final class View
{
    /** @var array<string, mixed> Values available to every template (e.g. the CSP nonce). */
    private array $shared = [];

    public function __construct(private readonly string $directory)
    {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], ?string $layout = null): string
    {
        $data = [...$this->shared, ...$data];
        $content = $this->renderFile($template, $data);
        return $layout === null ? $content : $this->renderFile($layout, [...$data, 'content' => $content]);
    }

    /** @param array<string, mixed> $data */
    private function renderFile(string $template, array $data): string
    {
        if (!preg_match('/^[a-zA-Z0-9_\/.-]+$/', $template) || str_contains($template, '..')) {
            throw new RuntimeException('View could not be rendered.');
        }
        $file = realpath($this->directory . '/' . $template . '.php');
        $root = realpath($this->directory);
        if ($file === false || $root === false || !str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('View could not be rendered.');
        }
        extract($data, EXTR_SKIP);
        $level = ob_get_level();
        ob_start();
        try {
            require $file;
            return (string) ob_get_clean();
        } catch (\Throwable $exception) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $exception;
        }
    }
}
