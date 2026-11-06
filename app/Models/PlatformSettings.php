<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/** Product name and contact details the platform owner can change without touching files. */
final class PlatformSettings extends Model
{
    public const DEFAULT_NAME = 'ExamCore CBT';
    public const DEFAULT_SHORT = 'ExamCore';
    public const DEFAULT_TAGLINE = 'Learn · Practice · Excel';
    public const KEYS = ['product_name', 'short_name', 'tagline', 'support_email'];

    /** @return array<string,string> only the keys that have been set */
    public function all(): array
    {
        $out = [];
        foreach ($this->rows('SELECT setting_key, setting_value FROM ' . $this->table('platform_settings')) as $row) {
            if (in_array($row['setting_key'], self::KEYS, true)) {
                $out[(string) $row['setting_key']] = (string) $row['setting_value'];
            }
        }
        return $out;
    }

    /**
     * Check what the owner typed. Names are plain text only (no tags or symbols that could break pages or emails).
     *
     * @param array<string,mixed> $input
     * @return array{0: array<string,string>, 1: array<string,string>} cleaned values, errors by field
     */
    public static function validate(array $input): array
    {
        $clean = [];
        $errors = [];
        $text = static fn (string $key): string => trim((string) preg_replace('/\s+/u', ' ', (string) ($input[$key] ?? '')));
        $name = $text('product_name');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 40 || preg_match('/^[\p{L}\p{N} .&\'\-]+$/u', $name) !== 1) {
            $errors['product_name'] = 'Use 2 to 40 letters, numbers or spaces (you may also use . & \' -).';
        }
        $clean['product_name'] = $name;
        $short = $text('short_name');
        if ($short !== '' && (mb_strlen($short) > 14 || preg_match('/^[\p{L}\p{N} .&\'\-]+$/u', $short) !== 1)) {
            $errors['short_name'] = 'Use up to 14 letters, numbers or spaces. This is the label under the app icon.';
        }
        $clean['short_name'] = $short;
        $tagline = $text('tagline');
        if ($tagline !== '' && (mb_strlen($tagline) > 80 || preg_match('/^[\p{L}\p{N} .,&\'\-·•|!?]+$/u', $tagline) !== 1)) {
            $errors['tagline'] = 'Use up to 80 characters of plain text.';
        }
        $clean['tagline'] = $tagline;
        $email = strtolower(trim((string) ($input['support_email'] ?? '')));
        if ($email !== '' && (mb_strlen($email) > 120 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            $errors['support_email'] = 'Enter a valid email address, or leave it empty.';
        }
        $clean['support_email'] = $email;
        return [$clean, $errors];
    }

    /** @param array<string,string> $values */
    public function save(array $values, ?int $userId): void
    {
        $now = gmdate('Y-m-d H:i:s');
        foreach (self::KEYS as $key) {
            $value = (string) ($values[$key] ?? '');
            if ($value === '') {
                $this->run('DELETE FROM ' . $this->table('platform_settings') . ' WHERE setting_key = ?', [$key]); // empty = back to the default
                continue;
            }
            $this->run('INSERT INTO ' . $this->table('platform_settings') . ' (setting_key, setting_value, updated_at, updated_by) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)', [$key, $value, $now, $userId]);
        }
    }

    public function reset(): void
    {
        $this->run('DELETE FROM ' . $this->table('platform_settings'));
    }
}
