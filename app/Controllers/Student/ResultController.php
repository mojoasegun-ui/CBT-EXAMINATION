<?php
declare(strict_types=1);

namespace ExamCore\Controllers\Student;

use ExamCore\Controllers\PortalController as BasePortal;
use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\TenantContext;
use ExamCore\Core\View;
use ExamCore\Models\AcademicCalendar;
use ExamCore\Models\Student;
use ExamCore\Services\ResultService;

/** A student's own results: released exam scores and the term report card. */
final class ResultController extends BasePortal
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf,
        private readonly TenantContext $tenant,
        private readonly Student $students,
        private readonly ResultService $results,
        private readonly AcademicCalendar $calendar,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    public function index(Request $request): Response
    {
        $student = $this->me();
        $rows = $this->results->studentResults($this->tenant->id(), (int) $student['id']);
        $visible = array_column(array_filter($rows, static fn ($r) => $r['visible']), 'exam_id');
        return $this->page($request, 'student/results/index', [
            'area' => 'student', 'title' => 'My results', 'student' => $student, 'rows' => $rows,
            'positions' => $this->results->positions($visible, (int) $student['id']),
            'term' => $this->calendar->current($this->tenant->id())['term'],
        ]);
    }

    public function report(Request $request): Response
    {
        $student = $this->me();
        $termId = (int) $request->query('term', $this->calendar->current($this->tenant->id())['term']['id'] ?? 0);
        $term = $this->results->term($this->tenant->id(), $termId) ?? throw HttpException::notFound('No term selected.');
        $card = $this->results->card($this->tenant->school(), $student, $term, true);
        return $this->page($request, 'school/results/report', ['title' => 'My report card', 'cards' => [$card], 'editable' => false, 'term' => $term, 'back' => url('/student/results')], 'layouts/print');
    }

    private function me(): array
    {
        return $this->students->find($this->tenant->id(), (int) $this->guard->id()) ?? throw HttpException::forbidden();
    }
}
