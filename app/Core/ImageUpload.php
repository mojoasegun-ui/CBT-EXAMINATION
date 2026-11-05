<?php
declare(strict_types=1);

namespace ExamCore\Core;

/**
 * Validates and stores an uploaded image under public/uploads.
 * The real type is read from the file bytes; browser-supplied names and MIME types are ignored.
 * SVG is refused because it can carry scripts.
 */
final class ImageUpload
{
    private const TYPES = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'];

    public function __construct(private readonly string $publicPath)
    {
    }

    /**
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file
     * @return array{path: string|null, error: string|null} path relative to public/
     */
    public function store(array $file, string $directory, string $prefix, int $maxBytes = 1_048_576, int $minSide = 32, int $maxSide = 4000): array
    {
        if ($file['size'] > $maxBytes) {
            return ['path' => null, 'error' => 'The image must be ' . round($maxBytes / 1048576, 1) . ' MB or smaller.'];
        }
        $info = @getimagesize($file['tmp_name']);
        $extension = is_array($info) ? (self::TYPES[$info[2]] ?? null) : null;
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ($extension === null || !in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            return ['path' => null, 'error' => 'Upload a PNG, JPG or WebP image. (SVG is not accepted for security reasons.)'];
        }
        if ($info[0] < $minSide || $info[1] < $minSide || $info[0] > $maxSide || $info[1] > $maxSide) {
            return ['path' => null, 'error' => "The image should be between {$minSide} and {$maxSide} pixels on each side."];
        }
        $directory = trim($directory, '/');
        if (!preg_match('#^uploads/[a-z0-9/_-]+$#', $directory)) {
            throw new \InvalidArgumentException('Invalid upload directory.');
        }
        $absolute = $this->publicPath . '/' . $directory;
        if (!is_dir($absolute) && !mkdir($absolute, 0755, true) && !is_dir($absolute)) {
            return ['path' => null, 'error' => 'The image could not be saved (storage is not writable).'];
        }
        $relative = $directory . '/' . $prefix . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $this->publicPath . '/' . $relative)) {
            return ['path' => null, 'error' => 'The image could not be saved.'];
        }
        return ['path' => $relative, 'error' => null];
    }

    /** Delete a previously stored file, but only inside the expected directory. */
    public function delete(?string $relative, string $directory): void
    {
        if (is_string($relative) && str_starts_with($relative, trim($directory, '/') . '/') && !str_contains($relative, '..')) {
            @unlink($this->publicPath . '/' . $relative);
        }
    }
}
