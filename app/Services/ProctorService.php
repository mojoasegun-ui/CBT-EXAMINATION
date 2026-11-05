<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Database;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Models\ExamSitting;

/**
 * Exam integrity. Face detection and matching run in the student's browser (free, works
 * offline); the server stores the evidence (small JPEG snapshots + scores) so a teacher can
 * review it. Nothing here trusts the browser blindly: every flag is backed by a photo.
 */
final class ProctorService
{
    public const FLAGS = ['no_face' => 'No face in view', 'multiple_faces' => 'More than one face', 'face_mismatch' => 'Face does not match', 'camera_off' => 'Camera turned off'];
    private const MAX_BYTES = 200_000;

    public function __construct(
        private readonly Database $database,
        private readonly ExamSitting $sittings,
        private readonly string $storageRoot,
    ) {
    }

    /** Validate and store a JPEG. Returns the path relative to the storage root. */
    public function storeImage(int $schoolId, int $examId, string $bytes): string
    {
        if ($bytes === '' || strlen($bytes) > self::MAX_BYTES || !str_starts_with($bytes, "\xFF\xD8\xFF")) {
            throw new HttpException(422, 'The photo could not be read. Please try again.');
        }
        $info = @getimagesizefromstring($bytes);
        if ($info === false || ($info[2] ?? 0) !== IMAGETYPE_JPEG || $info[0] > 1280 || $info[1] > 1280) {
            throw new HttpException(422, 'The photo could not be read. Please try again.');
        }
        $relative = sprintf('%d/%d/%s-%s.jpg', $schoolId, $examId, gmdate('Ymd-His'), bin2hex(random_bytes(6)));
        $file = $this->storageRoot . '/' . $relative;
        if (!is_dir(dirname($file)) && !@mkdir(dirname($file), 0750, true) && !is_dir(dirname($file))) {
            throw new \RuntimeException('Cannot create the proctoring folder.');
        }
        if (@file_put_contents($file, $bytes, LOCK_EX) === false) {
            throw new \RuntimeException('Cannot save the snapshot.');
        }
        return $relative;
    }

    public function path(string $relative): ?string
    {
        if (!preg_match('~^\d+/\d+/[\w-]+\.jpg$~', $relative)) {
            return null;
        }
        $file = $this->storageRoot . '/' . $relative;
        return is_file($file) ? $file : null;
    }

    /** Record the identity check done in the lobby onto the new sitting. */
    public function attachVerification(int $sittingId, array $check): void
    {
        $this->sittings->finish($sittingId, ['face_status' => $check['status'], 'face_score' => $check['score']]);
        $this->sittings->addSnapshot($sittingId, 'verify', $check['path'], $check['faces'], $check['score'], $check['flag']);
    }

    /**
     * Snapshot taken during the exam. A flagged snapshot counts as a warning.
     *
     * @return array{flag: ?string, warnings: int, flags: int}
     */
    public function snapshot(array $sitting, string $path, ?int $faces, ?float $score, ?string $flag): array
    {
        $flag = array_key_exists((string) $flag, self::FLAGS) ? $flag : null;
        $id = (int) $sitting['id'];
        $this->database->transaction(function () use ($id, $path, $faces, $score, $flag): void {
            $this->sittings->addSnapshot($id, 'random', $path, $faces, $score, $flag);
            if ($flag !== null) {
                $this->sittings->logEvents($id, [['type' => $flag, 'detail' => self::FLAGS[$flag]]]);
                $this->sittings->bumpFlags($id);
            }
        });
        $fresh = (array) $this->sittings->find($id);
        return ['flag' => $flag, 'warnings' => (int) $fresh['warnings'], 'flags' => (int) $fresh['flags']];
    }

    /** Seconds since this sitting's last snapshot (rate limiting). */
    public function secondsSinceLastSnapshot(int $sittingId): ?int
    {
        $last = $this->sittings->lastSnapshotAt($sittingId);
        return $last === null ? null : time() - (int) strtotime($last . ' UTC');
    }

    /** clean | check | suspicious, with the reasons a teacher should look at. */
    public static function trust(array $sitting): array
    {
        $reasons = [];
        $level = 'clean';
        $raise = static function (string $to, string $why) use (&$level, &$reasons): void {
            $reasons[] = $why;
            if ($to === 'suspicious' || $level === 'clean') {
                $level = $to;
            }
        };
        $face = (string) ($sitting['face_status'] ?? 'none');
        $flags = (int) ($sitting['flags'] ?? 0);
        $warnings = (int) ($sitting['warnings'] ?? 0);
        if ($face === 'mismatch') {
            $raise('suspicious', 'Face did not match the school photo');
        } elseif ($face === 'approved') {
            $raise('check', 'Admitted by invigilator PIN after a failed face check');
        }
        if ($flags >= 3) {
            $raise('suspicious', "{$flags} camera alerts");
        } elseif ($flags > 0) {
            $raise('check', "{$flags} camera alert" . ($flags === 1 ? '' : 's'));
        }
        if ($warnings - $flags >= 6) {
            $raise('suspicious', 'Left the exam screen ' . ($warnings - $flags) . ' times');
        } elseif ($warnings - $flags >= 2) {
            $raise('check', 'Left the exam screen ' . ($warnings - $flags) . ' times');
        }
        return ['level' => $level, 'reasons' => $reasons];
    }

    /** Delete snapshots older than $days (files and rows). */
    public function purge(int $days, int $limit = 500): int
    {
        $n = 0;
        foreach ($this->sittings->oldSnapshots(gmdate('Y-m-d H:i:s', time() - $days * 86400), $limit) as $row) {
            $file = $this->path((string) $row['path']);
            if ($file !== null) {
                @unlink($file);
            }
            $this->sittings->deleteSnapshot((int) $row['id']);
            $n++;
        }
        return $n;
    }
}
