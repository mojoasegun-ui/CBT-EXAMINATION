<?php
declare(strict_types=1);

namespace ExamCore\Controllers\School;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\Spreadsheet\Spreadsheet;
use ExamCore\Core\TenantContext;
use ExamCore\Core\View;
use ExamCore\Models\Department;
use ExamCore\Models\ImportBatch;
use ExamCore\Models\SchoolClass;
use ExamCore\Models\Student;
use ExamCore\Services\AuditService;
use ExamCore\Services\StudentService;
use RuntimeException;

final class StudentController extends SchoolController
{
    private const PER_PAGE = 50;

    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, TenantContext $tenant,
        private readonly Student $students,
        private readonly SchoolClass $classes,
        private readonly Department $departments,
        private readonly ImportBatch $imports,
        private readonly StudentService $service,
        private readonly AuditService $audit,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf, $tenant);
    }

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $page = max(1, (int) $request->query('page', 1));
        $sort = in_array($request->query('sort'), ['name', 'admission', 'class', 'newest'], true) ? (string) $request->query('sort') : 'name';
        $result = $this->students->search($this->schoolId(), $filters, $page, self::PER_PAGE, $sort);
        return $this->page($request, 'school/students/index', $this->base('Students') + [
            'rows' => $result['rows'],
            'total' => $result['total'],
            'pageNumber' => $page,
            'perPage' => self::PER_PAGE,
            'filters' => $filters,
            'sort' => $sort,
            'classes' => $this->classes->all($this->schoolId()),
            'departments' => $this->departments->all($this->schoolId(), true),
            'remaining' => $this->service->remainingCapacity($this->schoolId()),
            'activeCount' => $this->students->countActive($this->schoolId()),
            'recentImports' => $this->imports->recent($this->schoolId(), 3),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->page($request, 'school/students/form', $this->base('Enrol a student') + [
            'student' => null,
            'classes' => $this->classes->all($this->schoolId(), true),
            'departments' => $this->departments->all($this->schoolId(), true),
            'preselectClass' => (int) $request->query('class', 0),
        ]);
    }

    public function store(Request $request): Response
    {
        $check = $this->service->validate($this->schoolId(), $this->input($request));
        if ($check['errors'] !== []) {
            return $this->backWithErrors('/school/students/create', $check['errors'], $this->input($request));
        }
        try {
            $created = $this->service->create($this->schoolId(), $check['data']);
        } catch (RuntimeException $exception) {
            return $this->backWithErrors('/school/students/create', [], $this->input($request), $exception->getMessage());
        }
        $this->session->flash('credentials', ['name' => $check['data']['name'], 'admission_no' => $created['admission_no'], 'password' => $created['password']]);
        $next = $request->input('after') === 'another' ? '/school/students/create' . ($check['data']['class_id'] ? '?class=' . $check['data']['class_id'] : '') : '/school/students/' . $created['user_id'];
        return $this->redirectTo($next, $check['data']['name'] . ' enrolled as ' . $created['admission_no'] . '.');
    }

    public function show(Request $request, array $parameters): Response
    {
        $student = $this->find($parameters);
        return $this->page($request, 'school/students/show', $this->base($student['name']) + [
            'student' => $student,
            'classes' => $this->classes->all($this->schoolId(), true),
            'departments' => $this->departments->all($this->schoolId(), true),
            'credentials' => $this->session->pull('credentials'),
            'statuses' => Student::STATUSES,
        ]);
    }

    public function update(Request $request, array $parameters): Response
    {
        $student = $this->find($parameters);
        $check = $this->service->validate($this->schoolId(), $this->input($request), (int) $student['id']);
        if ($check['errors'] !== []) {
            return $this->backWithErrors('/school/students/' . $student['id'], $check['errors'], $this->input($request));
        }
        $this->service->update($this->schoolId(), (int) $student['id'], $check['data']);
        return $this->redirectTo('/school/students/' . $student['id'], 'Saved.');
    }

    public function photo(Request $request, array $parameters): Response
    {
        $student = $this->find($parameters);
        $file = $request->file('photo');
        $error = $file === null ? 'Choose a photo to upload.' : $this->service->uploadPhoto($this->schoolId(), (int) $student['id'], $file);
        return $this->redirectTo('/school/students/' . $student['id'], $error ?? 'Photo updated. It will be used for identity checks at exam time.', $error === null ? 'success' : 'error');
    }

    public function resetPassword(Request $request, array $parameters): Response
    {
        $student = $this->find($parameters);
        $password = $this->service->resetPassword($this->schoolId(), (int) $student['id']);
        $this->session->flash('credentials', ['name' => $student['name'], 'admission_no' => $student['admission_no'], 'password' => $password]);
        return $this->redirectTo('/school/students/' . $student['id'], 'New temporary password created. Any lockout has been cleared.');
    }

    // ---- Import -----------------------------------------------------------------------------

    public function importForm(Request $request): Response
    {
        return $this->page($request, 'school/students/import', $this->base('Import students') + [
            'classes' => $this->classes->all($this->schoolId(), true),
            'remaining' => $this->service->remainingCapacity($this->schoolId()),
            'recentImports' => $this->imports->recent($this->schoolId(), 5),
        ]);
    }

    public function template(Request $request): Response
    {
        return $this->download($this->service->template($this->schoolId()), 'student-import-template.xlsx');
    }

    public function analyse(Request $request): Response
    {
        $file = $request->file('file');
        if ($file === null) {
            return $this->redirectTo('/school/students/import', 'Choose an Excel or CSV file to upload.', 'error');
        }
        if ($file['size'] > 10 * 1048576) {
            return $this->redirectTo('/school/students/import', 'The file is larger than 10 MB. Split it into smaller files.', 'error');
        }
        try {
            $result = $this->service->analyseImport($this->schoolId(), $file['tmp_name'], $file['name']);
        } catch (RuntimeException $exception) {
            return $this->redirectTo('/school/students/import', $exception->getMessage(), 'error');
        }
        $batchId = $result['valid'] === [] ? null : $this->service->queueImport($this->schoolId(), (int) $this->guard->id(), $file['name'], $result['valid']);
        return $this->page($request, 'school/students/import-review', $this->base('Review import') + [
            'fileName' => $file['name'],
            'valid' => $result['valid'],
            'errors' => $result['errors'],
            'total' => $result['total'],
            'columns' => $result['columns'],
            'batchId' => $batchId,
            'classNames' => array_column($this->classes->all($this->schoolId()), 'name', 'id'),
        ]);
    }

    /** One chunk per call. The page calls this repeatedly (JSON) until done; works without JS via redirects. */
    public function process(Request $request, array $parameters): Response
    {
        $batchId = $this->idParam($parameters);
        // Release nothing extra here: the Session class never holds its lock, so the admin can keep
        // using other tabs while this runs.
        try {
            $progress = $this->service->processImportChunk($this->schoolId(), $batchId);
        } catch (RuntimeException $exception) {
            return $request->wantsJson() ? Response::json(['error' => $exception->getMessage()], 422)
                : $this->redirectTo('/school/students/import', $exception->getMessage(), 'error');
        }
        if ($request->wantsJson()) {
            return Response::json($progress + ['done_url' => url('/school/students/import/' . $batchId)]);
        }
        return $progress['status'] === 'completed'
            ? $this->redirectTo('/school/students/import/' . $batchId)
            : $this->page($request, 'school/students/import-progress', $this->base('Importing…') + ['progress' => $progress, 'batchId' => $batchId]);
    }

    public function importResult(Request $request, array $parameters): Response
    {
        $batch = $this->imports->find($this->schoolId(), $this->idParam($parameters)) ?? throw HttpException::notFound();
        if ($batch['status'] !== 'completed') {
            return $this->page($request, 'school/students/import-progress', $this->base('Importing…') + [
                'progress' => ['status' => $batch['status'], 'processed' => (int) $batch['processed_rows'], 'total' => (int) $batch['total_rows'], 'created' => (int) $batch['created_rows'], 'skipped' => (int) $batch['skipped_rows']],
                'batchId' => (int) $batch['id'],
            ]);
        }
        return $this->page($request, 'school/students/import-done', $this->base('Import complete') + [
            'batch' => $batch,
            'hasCredentials' => $batch['credentials'] !== null,
        ]);
    }

    public function importCredentials(Request $request, array $parameters): Response
    {
        $batchId = $this->idParam($parameters);
        $list = $this->service->importCredentials($this->schoolId(), $batchId);
        if ($list === []) {
            return $this->redirectTo('/school/students/import', 'These sign-in details have expired (they are kept for 24 hours). Reset passwords from each student’s page if needed.', 'error');
        }
        $this->audit->record('student.credentials_export', 'Downloaded sign-in details for an import', 'import', $batchId, schoolId: $this->schoolId());
        $students = array_column($this->students->findMany($this->schoolId(), array_column($list, 'user_id')), null, 'id');
        $rows = array_map(static fn (array $c): array => [$c['admission_no'], $c['name'], $students[$c['user_id']]['class_name'] ?? '', $c['password']], $list);
        return $this->download(Spreadsheet::xlsx(['Admission No', 'Name', 'Class', 'First Password'], $rows, 'Sign-in details'), 'student-sign-in-details.xlsx');
    }

    // ---- Slips & exports ---------------------------------------------------------------------

    /** Printable sign-in slips: for a fresh import (with passwords) or any selection (without). */
    public function slips(Request $request): Response
    {
        $batchId = (int) $request->query('import', 0);
        $credentials = [];
        if ($batchId > 0) {
            foreach ($this->service->importCredentials($this->schoolId(), $batchId) as $c) {
                $credentials[(int) $c['user_id']] = $c['password'];
            }
            $students = $this->students->findMany($this->schoolId(), array_keys($credentials));
        } else {
            $students = $this->students->all($this->schoolId(), $this->filters($request), 2000);
        }
        return $this->render('school/students/slips', [
            'students' => $students,
            'credentials' => $credentials,
            'school' => $this->tenant->school(),
            'loginUrl' => url('/student/login'),
            'title' => 'Sign-in slips',
        ]);
    }

    public function export(Request $request): Response
    {
        $students = $this->students->all($this->schoolId(), $this->filters($request), 50000);
        $rows = array_map(static fn (array $s): array => [
            $s['admission_no'], $s['name'], $s['class_name'] ?? '', $s['department_name'] ?? '', ucfirst((string) $s['gender']), $s['date_of_birth'] ?? '',
            $s['guardian_name'] ?? '', $s['guardian_phone'] ?? '', $s['guardian_email'] ?? '', ucfirst((string) $s['status']),
        ], $students);
        $header = ['Admission No', 'Name', 'Class', 'Department', 'Gender', 'Date of Birth', 'Guardian Name', 'Guardian Phone', 'Guardian Email', 'Status'];
        $this->audit->record('student.export', 'Exported ' . count($rows) . ' student record(s)', schoolId: $this->schoolId());
        return $request->query('format') === 'csv'
            ? new Response(Spreadsheet::csv($header, $rows), 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="students.csv"'])
            : $this->download(Spreadsheet::xlsx($header, $rows, 'Students'), 'students.xlsx');
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        $class = $request->query('class');
        return [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 80),
            'class_id' => $class === 'none' ? 0 : ((int) $class > 0 ? (int) $class : null),
            'department_id' => (int) $request->query('department', 0) ?: null,
            'status' => (string) $request->query('status', 'active'),
            'gender' => (string) $request->query('gender', ''),
        ];
    }

    private function download(string $binary, string $fileName): Response
    {
        return new Response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /** @return array<string, mixed> */
    private function find(array $parameters): array
    {
        return $this->students->find($this->schoolId(), $this->idParam($parameters)) ?? throw HttpException::notFound('That student could not be found.');
    }
}
