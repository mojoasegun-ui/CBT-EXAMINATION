<?php
declare(strict_types=1);

namespace ExamCore\Controllers;

use ExamCore\Core\Container;
use ExamCore\Core\Controller;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\View;
use ExamCore\Models\ExamType;
use ExamCore\Models\Subject;
use ExamCore\Services\ExamService;

final class ApiController extends Controller
{
    public function __construct(
        Container $container,
        View $view,
        private readonly ExamService $examService,
        private readonly ExamType $examTypeModel,
        private readonly Subject $subjectModel,
    ) {
        parent::__construct($container, $view);
    }

    public function exams(Request $request): Response
    {
        $typeSlug = $request->query('exam_type');
        if (!is_string($typeSlug) || !preg_match('/^[a-z0-9-]{1,40}$/', $typeSlug)) {
            return Response::json(['error' => 'Choose an exam type.'], 422);
        }
        $examType = $this->examTypeModel->findActiveBySlug($typeSlug);
        if ($examType === null) {
            return Response::json(['error' => 'Exam type is unavailable.'], 404);
        }

        $subjectSlug = $request->query('subject');
        if ($subjectSlug === null || $subjectSlug === '') {
            return Response::json(['subjects' => $this->examService->listSubjects((int) $examType['id']), 'exams' => []]);
        }
        if (!is_string($subjectSlug) || !preg_match('/^[a-z0-9-]{1,60}$/', $subjectSlug)) {
            return Response::json(['error' => 'Choose a subject.'], 422);
        }
        $subject = $this->subjectModel->findActiveBySlug($subjectSlug);
        if ($subject === null) {
            return Response::json(['error' => 'Subject is unavailable.'], 404);
        }

        return Response::json([
            'subjects' => $this->examService->listSubjects((int) $examType['id']),
            'exams' => $this->examService->listExams((int) $examType['id'], (int) $subject['id']),
        ]);
    }
}