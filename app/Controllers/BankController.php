<?php
declare(strict_types=1);

namespace ExamCore\Controllers;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\ImageUpload;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\TenantContext;
use ExamCore\Core\View;
use ExamCore\Models\BankQuestion;
use ExamCore\Models\SchoolSubject;
use ExamCore\Models\User;
use ExamCore\Services\QuestionBankService;
use RuntimeException;

/** Question bank for a school (/school/bank) and the platform bank (/console/bank). */
final class BankController extends PortalController
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf,
        private readonly BankQuestion $bank,
        private readonly QuestionBankService $service,
        private readonly SchoolSubject $subjects,
        private readonly ImageUpload $images,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    public function index(Request $request): Response
    {
        [$schoolId, $area] = $this->scope($request);
        $f = [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 80),
            'subject' => (string) $request->query('subject', ''),
            'status' => array_key_exists((string) $request->query('status'), BankQuestion::STATUSES) ? (string) $request->query('status') : '',
            'difficulty' => array_key_exists((string) $request->query('difficulty'), BankQuestion::DIFFICULTIES) ? (string) $request->query('difficulty') : '',
            'author' => $request->query('mine') === '1' ? (int) $this->guard->id() : null,
        ];
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->bank->search($schoolId, $f, $page);
        return $this->page($request, 'bank/index', $this->base($area, 'Question bank') + [
            'rows' => $result['rows'], 'total' => $result['total'], 'pageNumber' => $page, 'perPage' => 25, 'filters' => $f,
            'health' => $this->bank->health($schoolId), 'subjectList' => $this->subjectList($schoolId),
            'inReview' => $this->bank->countByStatus($schoolId, 'review'),
        ]);
    }

    public function create(Request $request): Response
    {
        [$schoolId, $area] = $this->scope($request);
        return $this->page($request, 'bank/form', $this->base($area, 'New question') + $this->formData($schoolId) + ['question' => null]);
    }

    public function store(Request $request): Response
    {
        [$schoolId, $area] = $this->scope($request);
        $check = $this->service->validate($schoolId, $this->input($request), $this->allowedSubjects($schoolId));
        $duplicate = $check['errors'] === [] ? $this->bank->duplicateOf($schoolId, $check['data']['fingerprint']) : null;
        if ($duplicate !== null && $request->input('allow_duplicate') !== '1') {
            $check['errors']['body'] = 'This question already exists in the bank (#' . $duplicate['id'] . '). Tick “Save anyway” if it is intentional.';
        }
        if ($check['errors'] !== []) {
            return $this->backWithErrors('/' . $area . '/bank/create', $check['errors'], $this->input($request));
        }
        $status = $request->input('intent') === 'submit' ? ($this->canReview() ? 'published' : 'review') : 'draft';
        $id = $this->service->create($schoolId, (int) $this->guard->id(), $check['data'], $status);
        $this->handleImage($request, $schoolId, $id, null);
        $next = $request->input('after') === 'another' ? '/' . $area . '/bank/create?subject=' . rawurlencode($check['data']['subject']) . '&topic=' . rawurlencode((string) $check['data']['topic']) : '/' . $area . '/bank/' . $id;
        return $this->redirectTo($next, ['draft' => 'Saved as a draft.', 'review' => 'Saved and sent for review.', 'published' => 'Saved and published.'][$status]);
    }

    public function edit(Request $request, array $parameters): Response
    {
        [$schoolId, $area] = $this->scope($request);
        $question = $this->find($schoolId, $parameters);
        return $this->page($request, 'bank/form', $this->base($area, 'Question #' . $question['id']) + $this->formData($schoolId) + [
            'question' => $question, 'canEdit' => $this->canEdit($question), 'canReview' => $this->canReview(),
        ]);
    }

    public function update(Request $request, array $parameters): Response
    {
        [$schoolId, $area] = $this->scope($request);
        $question = $this->find($schoolId, $parameters);
        if (!$this->canEdit($question)) {
            throw HttpException::forbidden('Only the author (before publishing) or a reviewer can edit this question.');
        }
        $check = $this->service->validate($schoolId, $this->input($request), $this->allowedSubjects($schoolId));
        $duplicate = $check['errors'] === [] ? $this->bank->duplicateOf($schoolId, $check['data']['fingerprint'], (int) $question['id']) : null;
        if ($duplicate !== null && $request->input('allow_duplicate') !== '1') {
            $check['errors']['body'] = 'Another question (#' . $duplicate['id'] . ') has the same text. Tick “Save anyway” if it is intentional.';
        }
        if ($check['errors'] !== []) {
            return $this->backWithErrors('/' . $area . '/bank/' . $question['id'], $check['errors'], $this->input($request));
        }
        // Editing a published question as a non-reviewer is blocked above; reviewers keep it published.
        $this->bank->update($schoolId, (int) $question['id'], $check['data']);
        $this->handleImage($request, $schoolId, (int) $question['id'], $question['image_path']);
        return $this->redirectTo('/' . $area . '/bank/' . $question['id'], 'Question saved.');
    }

    public function transition(Request $request, array $parameters): Response
    {
        [$schoolId, $area] = $this->scope($request);
        $question = $this->find($schoolId, $parameters);
        $action = (string) $request->input('action', '');
        if ($action === 'submit' && !$this->canEdit($question)) {
            throw HttpException::forbidden();
        }
        try {
            $message = $this->service->transition($schoolId, (int) $question['id'], $action, $this->canReview(), (int) $this->guard->id(), trim((string) $request->input('note', '')));
        } catch (RuntimeException $exception) {
            return $this->redirectTo('/' . $area . '/bank/' . $question['id'], $exception->getMessage(), 'error');
        }
        $back = (string) $request->input('back', '');
        return $this->redirectTo($back === 'review' ? '/' . $area . '/bank?status=review' : '/' . $area . '/bank/' . $question['id'], $message);
    }

    public function delete(Request $request, array $parameters): Response
    {
        [$schoolId, $area] = $this->scope($request);
        $question = $this->find($schoolId, $parameters);
        if ($question['status'] === 'published' || !$this->canEdit($question)) {
            return $this->redirectTo('/' . $area . '/bank/' . $question['id'], 'Published questions are archived, not deleted, so past results stay correct.', 'error');
        }
        $this->bank->delete($schoolId, (int) $question['id']);
        $this->images->delete($question['image_path'], $this->imageDir($schoolId));
        return $this->redirectTo('/' . $area . '/bank', 'Question deleted.', 'warning');
    }

    // ---- Import --------------------------------------------------------------------------------

    public function importForm(Request $request): Response
    {
        [$schoolId, $area] = $this->scope($request);
        return $this->page($request, 'bank/import', $this->base($area, 'Import questions') + ['subjectList' => $this->subjectList($schoolId), 'result' => $this->session->pull('import_result')]);
    }

    public function template(Request $request): Response
    {
        return new Response($this->service->template((string) $request->query('subject', '')), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="question-import-template.xlsx"',
        ]);
    }

    public function import(Request $request): Response
    {
        [$schoolId, $area] = $this->scope($request);
        $file = $request->file('file');
        if ($file === null) {
            return $this->redirectTo('/' . $area . '/bank/import', 'Choose an Excel or CSV file.', 'error');
        }
        $status = $request->input('status') === 'published' && $this->canReview() ? 'published' : ($request->input('status') === 'draft' ? 'draft' : 'review');
        $source = array_key_exists((string) $request->input('source'), BankQuestion::SOURCES) ? (string) $request->input('source') : 'teacher';
        try {
            $result = $this->service->import($schoolId, (int) $this->guard->id(), $file['tmp_name'], $file['name'], $this->allowedSubjects($schoolId), (string) $request->input('subject', ''), $status, $source);
        } catch (RuntimeException $exception) {
            return $this->redirectTo('/' . $area . '/bank/import', $exception->getMessage(), 'error');
        }
        $this->session->flash('import_result', $result);
        return $this->redirectTo('/' . $area . '/bank/import', sprintf('%d question(s) imported, %d duplicate(s) skipped, %d row(s) need fixing.', $result['created'], $result['duplicates'], count($result['errors'])),
            $result['created'] > 0 ? 'success' : 'warning');
    }

    // ---- Passages -------------------------------------------------------------------------------

    public function passages(Request $request): Response
    {
        [$schoolId, $area] = $this->scope($request);
        return $this->page($request, 'bank/passages', $this->base($area, 'Passages') + ['passages' => $this->bank->passages($schoolId), 'subjectList' => $this->subjectList($schoolId)]);
    }

    public function savePassage(Request $request): Response
    {
        [$schoolId, $area] = $this->scope($request);
        $subject = trim((string) $request->input('subject', ''));
        $title = mb_substr(trim((string) $request->input('title', '')), 0, 160);
        $body = trim((string) $request->input('body', ''));
        $allowed = $this->allowedSubjects($schoolId);
        if ($title === '' || mb_strlen($body) < 20 || $subject === '' || ($allowed !== null && !in_array($subject, $allowed, true))) {
            return $this->backWithErrors('/' . $area . '/bank/passages', [], $this->input($request), 'Choose a subject and give the passage a title and at least 20 characters of text.');
        }
        $id = (int) $request->input('id', 0);
        $this->bank->savePassage($schoolId, (int) $this->guard->id(), $id > 0 && $this->bank->passage($schoolId, $id) ? $id : null, $subject, $title, mb_substr($body, 0, 60000));
        return $this->redirectTo('/' . $area . '/bank/passages', 'Passage saved. Link questions to it from the question form.');
    }

    // ---- Platform bank for schools ------------------------------------------------------------

    public function platform(Request $request): Response
    {
        $this->requirePlatformAccess();
        $f = ['q' => mb_substr(trim((string) $request->query('q', '')), 0, 80), 'subject' => (string) $request->query('subject', ''), 'status' => 'published'];
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->bank->search(null, $f, $page);
        return $this->page($request, 'bank/platform', $this->base('school', 'Platform question bank') + [
            'rows' => $result['rows'], 'total' => $result['total'], 'pageNumber' => $page, 'perPage' => 25, 'filters' => $f, 'subjectList' => $this->bank->subjects(null),
        ]);
    }

    /** Copy chosen platform questions into the school's bank (as drafts, subject must exist in the school). */
    public function copy(Request $request): Response
    {
        $this->requirePlatformAccess();
        $schoolId = $this->service(TenantContext::class)->id();
        $allowed = $this->allowedSubjects($schoolId);
        $copied = $skipped = 0;
        foreach (array_slice(is_array($request->input('ids')) ? $request->input('ids') : [], 0, 200) as $id) {
            $source = $this->bank->find(null, (int) $id);
            if ($source === null || $source['status'] !== 'published' || !in_array($source['subject'], $allowed, true) || $this->bank->duplicateOf($schoolId, (string) $source['fingerprint']) !== null) {
                $skipped++;
                continue;
            }
            $this->bank->create($schoolId, (int) $this->guard->id(), ['status' => 'draft'] + array_diff_key($source, array_flip(['status', 'passage_id', 'image_path', 'review_note', 'reviewed_by', 'reviewed_at'])));
            $copied++;
        }
        return $this->redirectTo('/school/bank/platform', sprintf('Copied %d question(s) into your bank as drafts%s.', $copied, $skipped ? ', skipped ' . $skipped . ' (already copied or subject not offered)' : ''));
    }

    // ---- Helpers --------------------------------------------------------------------------------

    /** @return array{0: int|null, 1: string} */
    private function scope(Request $request): array
    {
        return str_starts_with($request->path(), '/console') ? [null, 'console'] : [$this->service(TenantContext::class)->id(), 'school'];
    }

    private function base(string $area, string $title): array
    {
        return ['area' => $area, 'title' => $title, 'canReview' => $this->canReview(), 'userId' => (int) $this->guard->id()];
    }

    private function canReview(): bool
    {
        return in_array($this->guard->role(), [User::ROLE_OWNER, User::ROLE_SCHOOL_ADMIN, User::ROLE_REVIEWER], true);
    }

    /** @param array<string, mixed> $question */
    private function canEdit(array $question): bool
    {
        return $this->canReview() || ((int) $question['created_by'] === (int) $this->guard->id() && in_array($question['status'], ['draft', 'review'], true));
    }

    /** @return list<string>|null null = any subject (platform bank) */
    private function allowedSubjects(?int $schoolId): ?array
    {
        return $schoolId === null ? null : array_column($this->subjects->all($schoolId, true), 'name');
    }

    /** @return list<string> */
    private function subjectList(?int $schoolId): array
    {
        return $this->allowedSubjects($schoolId) ?? array_values(array_unique([...$this->bank->subjects(null),
            'English Language', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Economics', 'Government', 'Literature in English', 'Geography', 'Civic Education', 'Agricultural Science', 'Commerce', 'Financial Accounting', 'Further Mathematics', 'Christian Religious Studies']));
    }

    /** @return array<string, mixed> */
    private function formData(?int $schoolId): array
    {
        return ['subjectList' => $this->subjectList($schoolId), 'passages' => $this->bank->passages($schoolId)];
    }

    private function imageDir(?int $schoolId): string
    {
        return $schoolId === null ? 'uploads/bank' : 'uploads/schools/' . $schoolId . '/questions';
    }

    private function handleImage(Request $request, ?int $schoolId, int $id, ?string $old): void
    {
        $file = $request->file('image');
        if ($request->input('remove_image') === '1' && $old !== null) {
            $this->bank->update($schoolId, $id, ['image_path' => null]);
            $this->images->delete($old, $this->imageDir($schoolId));
        }
        if ($file === null) {
            return;
        }
        $stored = $this->images->store($file, $this->imageDir($schoolId), 'q' . $id, 2_097_152, 16, 4000);
        if ($stored['error'] !== null) {
            $this->flash('Question saved, but the image was not: ' . $stored['error'], 'warning');
            return;
        }
        $this->bank->update($schoolId, $id, ['image_path' => $stored['path']]);
        $this->images->delete($old, $this->imageDir($schoolId));
    }

    private function requirePlatformAccess(): void
    {
        if (!$this->service(TenantContext::class)->hasFeature('global_question_bank')) {
            throw HttpException::forbidden('The platform question bank is included in the Pro and Enterprise plans. Ask your platform provider to upgrade your school.');
        }
    }

    /** @return array<string, mixed> */
    private function find(?int $schoolId, array $parameters): array
    {
        $id = filter_var($parameters['id'] ?? null, FILTER_VALIDATE_INT);
        return ($id ? $this->bank->find($schoolId, $id) : null) ?? throw HttpException::notFound('That question could not be found.');
    }
}
