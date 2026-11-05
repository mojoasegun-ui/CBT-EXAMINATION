<?php
declare(strict_types=1);

namespace ExamCore\Core;

use RuntimeException;

/**
 * File-backed PHP session that never holds its lock while application code runs.
 *
 * PHP normally locks the session file from session_start() until the request ends, so one
 * slow request (an import, a big report) makes every other click from the same browser wait
 * in line, which users experience as the app "hanging". Here the session is read once and
 * released immediately; each write re-opens it for a moment, applies the change on top of
 * the latest data, and releases it again.
 */
final class Session
{
    private bool $loaded = false;

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config, private readonly string $storagePath)
    {
    }

    /** Load the session (and enforce the idle timeout). Kept public for existing callers. */
    public function start(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->open();
        $now = time();
        $lifetime = (int) ($this->config['session']['lifetime'] ?? 7200);
        if (isset($_SESSION['_last_activity']) && $now - (int) $_SESSION['_last_activity'] > $lifetime) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        // Touch at most once a minute: avoids a file write on every single request.
        if ($now - (int) ($_SESSION['_last_activity'] ?? 0) >= 60) {
            $_SESSION['_last_activity'] = $now;
        }
        $this->close();
        $this->loaded = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->write(static function () use ($key, $value): void {
            $_SESSION[$key] = $value;
        });
    }

    public function remove(string $key): void
    {
        $this->start();
        if (!array_key_exists($key, $_SESSION)) {
            return;
        }
        $this->write(static function () use ($key): void {
            unset($_SESSION[$key]);
        });
    }

    /** Store a value for the next request only (status toasts, form errors, old input). */
    public function flash(string $key, mixed $value): void
    {
        $this->write(static function () use ($key, $value): void {
            $_SESSION['_flash'][$key] = $value;
        });
    }

    /** Read and clear a flashed value. */
    public function pull(string $key, mixed $default = null): mixed
    {
        $this->start();
        if (!isset($_SESSION['_flash']) || !array_key_exists($key, $_SESSION['_flash'])) {
            return $default;
        }
        $value = $_SESSION['_flash'][$key];
        $this->write(static function () use ($key): void {
            unset($_SESSION['_flash'][$key]);
        });
        return $value;
    }

    public function regenerate(): void
    {
        $this->start();
        $this->open();
        session_regenerate_id(true);
        $this->close();
    }

    public function destroy(): void
    {
        $this->start();
        $this->open();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
        $this->loaded = false;
    }

    /** Re-open, apply one change on top of the freshest data, release. */
    private function write(callable $change): void
    {
        $this->start();
        $this->open();
        $change();
        $this->close();
    }

    private function open(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (headers_sent()) {
            throw new RuntimeException('Session cannot be changed after output has been sent.');
        }
        if (!is_dir($this->storagePath) || !is_writable($this->storagePath)) {
            throw new RuntimeException('Session storage is unavailable.');
        }
        $sameSite = (string) ($this->config['session']['same_site'] ?? 'Lax');
        if (!in_array($sameSite, ['Strict', 'Lax', 'None'], true)) {
            $sameSite = 'Lax';
        }
        $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
        if ($sameSite === 'None' && !$https) {
            $sameSite = 'Lax';
        }
        session_save_path($this->storagePath);
        session_name((string) ($this->config['session']['name'] ?? 'examcore_session'));
        if (!session_start([
            'use_strict_mode' => 1,
            'use_only_cookies' => 1,
            'use_trans_sid' => 0,
            'cookie_httponly' => true,
            'cookie_secure' => $https,
            'cookie_samesite' => $sameSite,
            'cookie_lifetime' => 0,
            'cookie_path' => (string) ($this->config['session']['path'] ?? '/'),
            'gc_maxlifetime' => (int) ($this->config['session']['lifetime'] ?? 7200),
        ])) {
            throw new RuntimeException('Session could not be initialized.');
        }
    }

    private function close(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}
