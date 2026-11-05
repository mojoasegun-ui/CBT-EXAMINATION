<?php
declare(strict_types=1);

if (!function_exists('icon')) {
    /**
     * Inline SVG icon (24px grid, 2px stroke, Lucide-style). Decorative by default.
     * Usage: <?= icon('school', 'h-5 w-5') ?>
     */
    function icon(string $name, string $class = 'h-5 w-5', ?string $label = null): string
    {
        static $paths = [
            'dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
            'school' => '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/>',
            'layers' => '<path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
            'list-checks' => '<path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/>',
            'server' => '<rect width="20" height="8" x="2" y="2" rx="2"/><rect width="20" height="8" x="2" y="14" rx="2"/><path d="M6 6h.01"/><path d="M6 18h.01"/>',
            'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'graduation' => '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>',
            'lock' => '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
            'shield' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
            'menu' => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
            'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
            'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
            'moon' => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
            'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
            'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
            'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
            'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
            'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
            'check' => '<path d="M20 6 9 17l-5-5"/>',
            'check-circle' => '<path d="M21.8 10A10 10 0 1 1 17 3.34"/><path d="m9 11 3 3L22 4"/>',
            'alert' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
            'alert-circle' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
            'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
            'external' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
            'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>',
            'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
            'copy' => '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
            'key' => '<path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/>',
            'refresh' => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
            'trash' => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
            'eye' => '<path d="M2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 19.88 0 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-19.88 0"/><circle cx="12" cy="12" r="3"/>',
            'activity' => '<path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/>',
            'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>',
            'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
            'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
            'sparkles' => '<path d="M9.94 15.5A2 2 0 0 0 8.5 14.06l-6.14-1.58a.5.5 0 0 1 0-.96L8.5 9.94A2 2 0 0 0 9.94 8.5l1.58-6.14a.5.5 0 0 1 .96 0L14.06 8.5A2 2 0 0 0 15.5 9.94l6.14 1.58a.5.5 0 0 1 0 .96L15.5 14.06a2 2 0 0 0-1.44 1.44l-1.58 6.14a.5.5 0 0 1-.96 0z"/>',
            'book' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
            'arrow-left' => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
            'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
            'palette' => '<circle cx="13.5" cy="6.5" r="1.2"/><circle cx="17.5" cy="10.5" r="1.2"/><circle cx="8.5" cy="7.5" r="1.2"/><circle cx="6.5" cy="12.5" r="1.2"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.65-.75 1.65-1.69 0-.44-.18-.84-.44-1.13-.29-.29-.44-.65-.44-1.13a1.64 1.64 0 0 1 1.67-1.67h2c3.05 0 5.55-2.5 5.55-5.55C21.97 6.01 17.46 2 12 2z"/>',
            'sliders' => '<path d="M21 4h-7"/><path d="M10 4H3"/><path d="M21 12h-9"/><path d="M8 12H3"/><path d="M21 20h-5"/><path d="M12 20H3"/><path d="M14 2v4"/><path d="M8 10v4"/><path d="M16 18v4"/>',
            'file-text' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
            'chart' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
            'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
            'phone' => '<rect width="14" height="20" x="5" y="2" rx="2"/><path d="M12 18h.01"/>',
            'wifi-off' => '<path d="M12 20h.01"/><path d="M8.5 16.43a5 5 0 0 1 7 0"/><path d="M5 12.86a10 10 0 0 1 5.17-2.69"/><path d="M19 12.86a10 10 0 0 0-2-1.52"/><path d="M2 8.82a15 15 0 0 1 4.18-2.64"/><path d="M22 8.82a15 15 0 0 0-11.29-3.76"/><path d="m2 2 20 20"/>',
            'scan-face' => '<path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01"/><path d="M15 9h.01"/>',
            'library' => '<path d="m16 6 4 14"/><path d="M12 6v14"/><path d="M8 8v12"/><path d="M4 4v16"/>',
            'globe' => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
        ];
        $body = $paths[$name] ?? $paths['info'];
        $a11y = $label === null ? 'aria-hidden="true" focusable="false"' : 'role="img" aria-label="' . e($label) . '"';
        return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ' . $a11y . '>' . $body . '</svg>';
    }
}

if (!function_exists('field_error')) {
    /** Inline validation message for a form field, or an empty string. @param array<string,string> $errors */
    function field_error(array $errors, string $field): string
    {
        $message = trim((string) ($errors[$field] ?? ''));
        if ($message === '') {
            return '';
        }
        return '<p class="field-error" id="' . e($field) . '-error">' . icon('alert-circle', 'h-3.5 w-3.5 shrink-0') . e($message) . '</p>';
    }
}

if (!function_exists('field')) {
    /**
     * One labelled form control with hint and error, styled consistently.
     *
     * @param array<string,string> $errors
     * @param array{type?: string, hint?: string, options?: array<string|int,string>, placeholder?: string, required?: bool, attrs?: string, class?: string, empty?: string} $o
     */
    function field(string $name, string $label, mixed $value, array $errors = [], array $o = []): string
    {
        $type = $o['type'] ?? 'text';
        $id = 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', $name);
        $error = trim((string) ($errors[$name] ?? ''));
        $aria = $error !== '' ? ' aria-invalid="true" aria-describedby="' . e($name) . '-error"' : '';
        $required = !empty($o['required']) ? ' required' : '';
        $attrs = ($o['attrs'] ?? '') . $required . $aria;
        $placeholder = isset($o['placeholder']) ? ' placeholder="' . e($o['placeholder']) . '"' : '';
        $invalid = $error !== '' ? ' input-error' : '';
        $hint = isset($o['hint']) && $o['hint'] !== '' ? ' <span class="label-hint">· ' . e($o['hint']) . '</span>' : '';
        $html = '<div class="' . e($o['class'] ?? '') . '"><label class="label" for="' . $id . '">' . e($label) . $hint . '</label>';
        if ($type === 'select') {
            $html .= '<select class="select' . $invalid . '" id="' . $id . '" name="' . e($name) . '"' . $attrs . '>';
            if (array_key_exists('empty', $o)) {
                $html .= '<option value="">' . e($o['empty']) . '</option>';
            }
            foreach ($o['options'] ?? [] as $optionValue => $optionLabel) {
                $html .= '<option value="' . e($optionValue) . '"' . ((string) $optionValue === (string) $value ? ' selected' : '') . '>' . e($optionLabel) . '</option>';
            }
            $html .= '</select>';
        } elseif ($type === 'textarea') {
            $html .= '<textarea class="textarea' . $invalid . '" id="' . $id . '" name="' . e($name) . '" rows="3"' . $placeholder . $attrs . '>' . e($value) . '</textarea>';
        } else {
            $html .= '<input class="input' . $invalid . '" id="' . $id . '" name="' . e($name) . '" type="' . e($type) . '" value="' . e($value) . '"' . $placeholder . $attrs . '>';
        }
        return $html . field_error($errors, $name) . '</div>';
    }
}

if (!function_exists('rich_text')) {
    /**
     * Safe question formatting without a heavy maths library: escapes everything, then
     * x^2 / x^{10} → superscript, H_2O / a_{n} → subscript, **bold**, line breaks.
     */
    function rich_text(?string $text): string
    {
        $html = e((string) $text);
        $html = preg_replace('/\^\{([^}]{1,20})\}/u', '<sup>$1</sup>', $html) ?? $html;
        $html = preg_replace('/\^(-?[\p{L}\p{N}.]{1,6})/u', '<sup>$1</sup>', $html) ?? $html;
        $html = preg_replace('/(?<=[\p{L}\p{N})\]])_\{([^}]{1,20})\}/u', '<sub>$1</sub>', $html) ?? $html;
        $html = preg_replace('/(?<=[\p{L})\]])_(\d{1,3})/u', '<sub>$1</sub>', $html) ?? $html;
        $html = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $html) ?? $html;
        return nl2br($html, false);
    }
}

if (!function_exists('initials')) {
    /** "Kings College Lagos" → "KC" */
    function initials(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name)) ?: [];
        $letters = '';
        foreach ($words as $word) {
            if ($word !== '' && preg_match('/\p{L}/u', $word)) {
                $letters .= mb_strtoupper(mb_substr($word, 0, 1));
            }
            if (mb_strlen($letters) === 2) {
                break;
            }
        }
        return $letters !== '' ? $letters : '?';
    }
}

if (!function_exists('hex_to_rgb_channels')) {
    /** "#4F46E5" → "79 70 229", for the --brand CSS variable. */
    function hex_to_rgb_channels(?string $hex, string $fallback = '79 70 229'): string
    {
        if (!is_string($hex) || !preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $hex, $m)) {
            return $fallback;
        }
        return hexdec($m[1]) . ' ' . hexdec($m[2]) . ' ' . hexdec($m[3]);
    }
}

if (!function_exists('brand_tint')) {
    /** A lighter partner shade of a brand colour (mixed toward white), as RGB channels, for gradients. */
    function brand_tint(?string $hex, float $amount = 0.38): string
    {
        if (!is_string($hex) || !preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $hex, $m)) {
            return '168 85 247';
        }
        $mix = static fn (string $pair): int => (int) round(hexdec($pair) + (255 - hexdec($pair)) * $amount);
        return $mix($m[1]) . ' ' . $mix($m[2]) . ' ' . $mix($m[3]);
    }
}

if (!function_exists('brand_ink')) {
    /** Black or white text channels, whichever reads better on the given brand colour (WCAG luminance). */
    function brand_ink(?string $hex): string
    {
        if (!is_string($hex) || !preg_match('/^#([0-9a-f]{6})$/i', $hex)) {
            return '255 255 255';
        }
        $channel = static function (string $pair): float {
            $c = hexdec($pair) / 255;
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };
        $l = 0.2126 * $channel(substr($hex, 1, 2)) + 0.7152 * $channel(substr($hex, 3, 2)) + 0.0722 * $channel(substr($hex, 5, 2));
        return (1.05 / ($l + 0.05)) >= (($l + 0.05) / 0.05) ? '255 255 255' : '15 23 42';
    }
}
