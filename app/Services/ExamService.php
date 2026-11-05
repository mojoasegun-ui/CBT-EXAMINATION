<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Models\Exam;
use ExamCore\Models\ExamType;
use ExamCore\Models\Question;
use ExamCore\Models\Subject;

final class ExamService
{
    public function __construct(
        private readonly ExamType $examTypes,
        private readonly Subject $subjects,
        private readonly Exam $exams,
        private readonly Question $questions,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function listExamTypes(): array
    {
        return $this->examTypes->listActive();
    }

    /** @return list<array<string, mixed>> */
    public function listSubjects(int $examTypeId): array
    {
        return $this->subjects->listForExamType($examTypeId);
    }

    /** @return list<array<string, mixed>> */
    public function listExams(int $examTypeId, int $subjectId): array
    {
        return $this->exams->listAvailable($examTypeId, $subjectId);
    }

    /** @return array{exam: array<string, mixed>, questions: list<array<string, mixed>>}|null */
    public function getExamWithQuestions(int $id): ?array
    {
        $exam = $this->exams->findAvailable($id);
        if ($exam === null) {
            return null;
        }
        $questions = $this->questions->listForExam($id);
        if ($questions === []) {
            return null;
        }
        return ['exam' => $exam, 'questions' => $questions];
    }
}