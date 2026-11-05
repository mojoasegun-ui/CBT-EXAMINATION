<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Database;
use ExamCore\Core\Spreadsheet\Spreadsheet;
use ExamCore\Models\BankQuestion;
use RuntimeException;

/** Writing, reviewing and importing questions. $schoolId null = platform bank. */
final class QuestionBankService
{
    public const MAX_IMPORT = 3000;
    private const HEADERS = [
        'subject' => ['subject'], 'topic' => ['topic'], 'body' => ['question', 'questiontext', 'question_text', 'stem'],
        'option_a' => ['optiona', 'a'], 'option_b' => ['optionb', 'b'], 'option_c' => ['optionc', 'c'], 'option_d' => ['optiond', 'd'], 'option_e' => ['optione', 'e'],
        'correct_option' => ['answer', 'correct', 'correctoption', 'correctanswer', 'key'], 'explanation' => ['explanation', 'solution', 'workings'],
        'difficulty' => ['difficulty'], 'level' => ['level', 'class'], 'exam_body' => ['exambody', 'exam', 'body'], 'year' => ['year'], 'marks' => ['marks', 'mark', 'score'],
    ];

    public function __construct(private readonly Database $database, private readonly BankQuestion $bank, private readonly AuditService $audit)
    {
    }

    /**
     * @param array<string, mixed> $in
     * @param list<string>|null $allowedSubjects null = any subject (platform bank)
     * @return array{errors: array<string, string>, data: array<string, mixed>}
     */
    public function validate(?int $schoolId, array $in, ?array $allowedSubjects): array
    {
        $errors = [];
        $text = static fn (string $k, int $max = 5000): string => mb_substr(trim(str_replace("\r\n", "\n", (string) ($in[$k] ?? ''))), 0, $max);
        $subject = $text('subject', 80);
        if ($subject === '' || ($allowedSubjects !== null && !in_array($subject, $allowedSubjects, true))) {
            $errors['subject'] = 'Choose a subject' . ($allowedSubjects !== null ? ' your school offers.' : '.');
        }
        $body = $text('body');
        if (mb_strlen($body) < 5) {
            $errors['body'] = 'Write the question (at least 5 characters).';
        }
        $options = [];
        foreach (['a', 'b', 'c', 'd', 'e'] as $letter) {
            $options[$letter] = $text('option_' . $letter, 1000);
        }
        if ($options['a'] === '' || $options['b'] === '') {
            $errors['option_a'] = 'At least options A and B are required.';
        }
        foreach (['c' => 'b', 'd' => 'c', 'e' => 'd'] as $letter => $before) {
            if ($options[$letter] !== '' && $options[$before] === '') {
                $errors['option_' . $letter] = 'Fill the options in order without gaps (option ' . strtoupper($before) . ' is empty).';
            }
        }
        $filled = array_keys(array_filter($options, static fn ($v) => $v !== ''));
        if (count(array_unique(array_map('mb_strtolower', array_intersect_key($options, array_flip($filled))))) !== count($filled)) {
            $errors['option_b'] = 'Two options are identical.';
        }
        $answer = strtolower($text('correct_option', 1));
        if (!in_array($answer, $filled, true)) {
            $errors['correct_option'] = 'Choose the correct answer from the filled options.';
        }
        $year = $text('year', 4);
        if ($year !== '' && (!ctype_digit($year) || (int) $year < 1970 || (int) $year > (int) date('Y') + 1)) {
            $errors['year'] = 'Enter a valid year.';
        }
        $marks = $text('marks', 6) === '' ? 1.0 : (float) $text('marks', 6);
        if ($marks <= 0 || $marks > 100) {
            $errors['marks'] = 'Marks must be between 0.5 and 100.';
        }
        $passageId = (int) ($in['passage_id'] ?? 0);
        if ($passageId > 0 && $this->bank->passage($schoolId, $passageId) === null) {
            $errors['passage_id'] = 'Choose a passage from this bank.';
        }
        $pick = static fn (string $k, array $list, string $default): string => array_key_exists($v = $text($k, 20), $list) ? $v : $default;

        return ['errors' => $errors, 'data' => [
            'subject' => $subject, 'topic' => $text('topic', 120) ?: null, 'body' => $body,
            'option_a' => $options['a'], 'option_b' => $options['b'], 'option_c' => $options['c'] ?: null, 'option_d' => $options['d'] ?: null, 'option_e' => $options['e'] ?: null,
            'correct_option' => strtoupper($answer), 'explanation' => $text('explanation') ?: null, 'marks' => $marks,
            'difficulty' => $pick('difficulty', BankQuestion::DIFFICULTIES, 'medium'), 'level' => $pick('level', BankQuestion::LEVELS, 'any'),
            'source' => $pick('source', BankQuestion::SOURCES, 'teacher'), 'exam_body' => $pick('exam_body', BankQuestion::BODIES, '') ?: null,
            'year' => $year === '' ? null : (int) $year, 'passage_id' => $passageId > 0 ? $passageId : null,
            'fingerprint' => self::fingerprint($body),
        ]];
    }

    /** Normalised text hash: catches the same question re-typed with different spacing or case. */
    public static function fingerprint(string $body): string
    {
        return sha1(preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($body)) ?? '');
    }

    /** @param array<string, mixed> $data validated */
    public function create(?int $schoolId, int $userId, array $data, string $status): int
    {
        $id = $this->bank->create($schoolId, $userId, $data + ['status' => $status]);
        $this->audit->record('bank.created', 'Added a ' . $data['subject'] . ' question', 'question', $id, schoolId: $schoolId);
        return $id;
    }

    /**
     * Move a question through the workflow. Teachers submit; reviewers/admins approve or send back.
     */
    public function transition(?int $schoolId, int $id, string $action, bool $canReview, int $userId, string $note = ''): string
    {
        $question = $this->bank->find($schoolId, $id) ?? throw new RuntimeException('Question not found.');
        [$from, $to, $message] = match ($action) {
            'submit' => [['draft'], 'review', 'Sent for review.'],
            'approve' => [['review', 'draft'], 'published', 'Published. It can now be used in exams.'],
            'reject' => [['review', 'published'], 'draft', 'Sent back to the author with your note.'],
            'archive' => [['draft', 'review', 'published'], 'archived', 'Archived. It will not be used in new exams.'],
            'restore' => [['archived'], 'draft', 'Restored as a draft.'],
            default => throw new RuntimeException('Unknown action.'),
        };
        if (in_array($action, ['approve', 'reject', 'archive', 'restore'], true) && !$canReview) {
            throw new RuntimeException('Only reviewers and administrators can do that.');
        }
        $past = ['submit' => 'submitted', 'approve' => 'approved', 'reject' => 'sent back', 'archive' => 'archived', 'restore' => 'restored'][$action];
        if (!in_array($question['status'], $from, true)) {
            throw new RuntimeException('This question is ' . strtolower(BankQuestion::STATUSES[$question['status']]) . ', so it cannot be ' . $past . ' now.');
        }
        $this->bank->update($schoolId, $id, ['status' => $to, 'review_note' => $action === 'reject' ? mb_substr($note, 0, 255) : null]
            + (in_array($action, ['approve', 'reject'], true) ? ['reviewed_by' => $userId, 'reviewed_at' => gmdate('Y-m-d H:i:s')] : []));
        $this->audit->record('bank.' . $action, ucfirst($past) . ' a ' . $question['subject'] . ' question', 'question', $id, schoolId: $schoolId);
        return $message;
    }

    /**
     * Validate and insert questions from a spreadsheet in one transaction.
     *
     * @param list<string>|null $allowedSubjects
     * @return array{created: int, duplicates: int, errors: list<array{row: int, messages: list<string>}>}
     */
    public function import(?int $schoolId, int $userId, string $path, string $fileName, ?array $allowedSubjects, string $defaultSubject, string $status, string $source): array
    {
        $rows = Spreadsheet::read($path, $fileName);
        if (count($rows) < 2) {
            throw new RuntimeException('The file is empty. Download the template to see the layout.');
        }
        if (count($rows) - 1 > self::MAX_IMPORT) {
            throw new RuntimeException('Import at most ' . number_format(self::MAX_IMPORT) . ' questions per file.');
        }
        $map = [];
        foreach (array_shift($rows) as $i => $title) {
            $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $title) ?? '');
            foreach (self::HEADERS as $field => $aliases) {
                if (!isset($map[$field]) && in_array($key, $aliases, true)) {
                    $map[$field] = $i;
                }
            }
        }
        if (!isset($map['body'], $map['option_a'], $map['correct_option'])) {
            throw new RuntimeException('Columns "Question", "Option A" and "Answer" are required. Download the template.');
        }
        $valid = [];
        $errors = [];
        $seen = [];
        $duplicates = 0;
        foreach ($rows as $index => $row) {
            if (implode('', $row) === '') {
                continue;
            }
            $input = ['source' => $source];
            foreach ($map as $field => $col) {
                $input[$field] = $row[$col] ?? '';
            }
            $input['subject'] = ($input['subject'] ?? '') !== '' ? $input['subject'] : $defaultSubject;
            $input['difficulty'] = strtolower((string) ($input['difficulty'] ?? ''));
            $input['level'] = strtolower((string) ($input['level'] ?? ''));
            $input['exam_body'] = strtoupper((string) ($input['exam_body'] ?? ''));
            $check = $this->validate($schoolId, $input, $allowedSubjects);
            if ($check['errors'] !== []) {
                $errors[] = ['row' => $index + 2, 'messages' => array_values(array_unique($check['errors']))];
                continue;
            }
            $fp = $check['data']['fingerprint'];
            if (isset($seen[$fp]) || $this->bank->duplicateOf($schoolId, $fp) !== null) {
                $duplicates++;
                continue;
            }
            $seen[$fp] = true;
            $valid[] = $check['data'];
        }
        $this->database->transaction(function () use ($schoolId, $userId, $valid, $status): void {
            foreach ($valid as $data) {
                $this->bank->create($schoolId, $userId, $data + ['status' => $status]);
            }
        });
        if ($valid !== []) {
            $this->audit->record('bank.import', 'Imported ' . count($valid) . ' question(s) from ' . $fileName, meta: ['duplicates' => $duplicates], schoolId: $schoolId);
        }
        return ['created' => count($valid), 'duplicates' => $duplicates, 'errors' => $errors];
    }

    public function template(string $subject): string
    {
        return Spreadsheet::xlsx(
            ['Subject', 'Topic', 'Question', 'Option A', 'Option B', 'Option C', 'Option D', 'Option E', 'Answer', 'Explanation', 'Difficulty', 'Level', 'Exam Body', 'Year', 'Marks'],
            [
                [$subject ?: 'Mathematics', 'Indices', 'Simplify 2^3 × 2^2', '2^5', '2^6', '4^5', '4^6', '', 'A', 'Add the powers: 2^(3+2) = 2^5 = 32.', 'easy', 'jss', 'WAEC', '', '1'],
                [$subject ?: 'Chemistry', 'Water', 'What is the chemical formula of water?', 'H_2O', 'HO_2', 'H_2O_2', 'OH', '', 'A', 'Two hydrogen atoms bond with one oxygen atom.', 'easy', 'ss', '', '', '1'],
            ],
            'Questions',
        );
    }
}
