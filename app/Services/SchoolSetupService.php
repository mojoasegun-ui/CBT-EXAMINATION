<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Database;
use ExamCore\Models\AcademicCalendar;
use ExamCore\Models\Department;
use ExamCore\Models\GradingScale;
use ExamCore\Models\SchoolClass;
use ExamCore\Models\SchoolSubject;

/**
 * Gives a new school a working structure on day one: calendar, departments, JSS/SS classes,
 * the standard Nigerian subject list, sensible class–subject offerings and a WAEC-style
 * grading scale. Everything is editable afterwards and nothing is overwritten if it exists.
 */
final class SchoolSetupService
{
    private const DEPARTMENTS = [
        ['General', 'GEN', 'Junior secondary and subjects shared by all departments.'],
        ['Science', 'SCI', 'Physics, Chemistry, Biology and related subjects.'],
        ['Commercial', 'COM', 'Economics, Accounting, Commerce and business subjects.'],
        ['Arts', 'ART', 'Literature, Government, History, languages and the arts.'],
    ];

    /** name => [code, category, department name|null, offered in (jss|ss|both|none)] */
    private const SUBJECTS = [
        'English Language' => ['ENG', 'core', null, 'both'],
        'Mathematics' => ['MTH', 'core', null, 'both'],
        'Civic Education' => ['CVE', 'core', null, 'both'],
        'Computer Studies' => ['CMP', 'core', null, 'both'],
        'Basic Science' => ['BSC', 'general', 'General', 'jss'],
        'Basic Technology' => ['BTC', 'general', 'General', 'jss'],
        'Social Studies' => ['SST', 'general', 'General', 'jss'],
        'Business Studies' => ['BUS', 'general', 'General', 'jss'],
        'Cultural and Creative Arts' => ['CCA', 'general', 'General', 'jss'],
        'Physical and Health Education' => ['PHE', 'general', 'General', 'jss'],
        'Physics' => ['PHY', 'science', 'Science', 'ss'],
        'Chemistry' => ['CHM', 'science', 'Science', 'ss'],
        'Biology' => ['BIO', 'science', 'Science', 'ss'],
        'Further Mathematics' => ['FMT', 'science', 'Science', 'none'],
        'Agricultural Science' => ['AGR', 'science', 'Science', 'both'],
        'Geography' => ['GEO', 'science', 'Science', 'none'],
        'Technical Drawing' => ['TDR', 'technical', 'Science', 'none'],
        'Economics' => ['ECO', 'commercial', 'Commercial', 'ss'],
        'Commerce' => ['CMR', 'commercial', 'Commercial', 'none'],
        'Financial Accounting' => ['ACC', 'commercial', 'Commercial', 'none'],
        'Office Practice' => ['OFP', 'commercial', 'Commercial', 'none'],
        'Marketing' => ['MKT', 'commercial', 'Commercial', 'none'],
        'Literature in English' => ['LIT', 'arts', 'Arts', 'none'],
        'Government' => ['GOV', 'arts', 'Arts', 'none'],
        'History' => ['HIS', 'arts', 'Arts', 'none'],
        'Christian Religious Studies' => ['CRS', 'arts', 'Arts', 'both'],
        'Islamic Religious Studies' => ['IRS', 'arts', 'Arts', 'none'],
        'Fine Arts' => ['FNA', 'arts', 'Arts', 'none'],
        'Music' => ['MUS', 'arts', 'Arts', 'none'],
        'French' => ['FRN', 'language', 'Arts', 'none'],
        'Yoruba' => ['YOR', 'language', 'Arts', 'none'],
        'Igbo' => ['IGB', 'language', 'Arts', 'none'],
        'Hausa' => ['HAU', 'language', 'Arts', 'none'],
        'Data Processing' => ['DPR', 'technical', null, 'none'],
    ];

    public function __construct(
        private readonly Database $database,
        private readonly AcademicCalendar $calendar,
        private readonly Department $departments,
        private readonly SchoolClass $classes,
        private readonly SchoolSubject $subjects,
        private readonly GradingScale $grading,
    ) {
    }

    public function needsSetup(int $schoolId): bool
    {
        return $this->classes->all($schoolId) === [] && $this->subjects->all($schoolId) === [];
    }

    /** @return array{departments: int, classes: int, subjects: int, sessions: int} what was created */
    public function seedDefaults(int $schoolId, ?string $today = null): array
    {
        $today ??= gmdate('Y-m-d');
        return $this->database->transaction(function () use ($schoolId, $today): array {
            $created = ['departments' => 0, 'classes' => 0, 'subjects' => 0, 'sessions' => 0];

            $departmentIds = [];
            foreach ($this->departments->all($schoolId) as $department) {
                $departmentIds[$department['name']] = (int) $department['id'];
            }
            foreach (self::DEPARTMENTS as [$name, $code, $description]) {
                if (!isset($departmentIds[$name])) {
                    $departmentIds[$name] = $this->departments->create($schoolId, $name, $code, $description);
                    $created['departments']++;
                }
            }

            $classIds = ['jss' => [], 'ss' => []];
            foreach (['JSS 1', 'JSS 2', 'JSS 3', 'SS 1', 'SS 2', 'SS 3'] as $level) {
                if (!$this->classes->exists($schoolId, $level, 'A')) {
                    $id = $this->classes->create($schoolId, $level, 'A', str_starts_with($level, 'JSS') ? $departmentIds['General'] : null, null, null);
                    $classIds[str_starts_with($level, 'JSS') ? 'jss' : 'ss'][] = $id;
                    $created['classes']++;
                }
            }

            $subjectIds = ['jss' => [], 'ss' => []];
            foreach (self::SUBJECTS as $name => [$code, $category, $departmentName, $offeredIn]) {
                if ($this->subjects->nameTaken($schoolId, $name)) {
                    continue;
                }
                $id = $this->subjects->create($schoolId, $name, $code, $category, $departmentName === null ? null : ($departmentIds[$departmentName] ?? null));
                $created['subjects']++;
                if ($offeredIn === 'jss' || $offeredIn === 'both') {
                    $subjectIds['jss'][] = $id;
                }
                if ($offeredIn === 'ss' || $offeredIn === 'both') {
                    $subjectIds['ss'][] = $id;
                }
            }
            // Offer the usual subjects in the newly created classes (only when both are new).
            foreach (['jss', 'ss'] as $stage) {
                foreach ($classIds[$stage] as $classId) {
                    $this->subjects->syncClass($schoolId, $classId, array_fill_keys($subjectIds[$stage], null), $subjectIds[$stage], []);
                }
            }

            if ($this->calendar->current($schoolId)['session'] === null) {
                $year = (int) substr($today, 0, 4);
                $startYear = (int) substr($today, 5, 2) >= 8 ? $year : $year - 1; // Sessions start in September.
                $name = $startYear . '/' . ($startYear + 1);
                if (!$this->calendar->sessionExists($schoolId, $name)) {
                    $this->calendar->createSession($schoolId, $startYear);
                    $created['sessions']++;
                }
                $term = $this->calendar->termOn($schoolId, $today);
                if ($term !== null) {
                    $this->calendar->setCurrentTerm($schoolId, (int) $term['id']);
                }
            }

            if ($this->grading->all($schoolId) === []) {
                $this->grading->replace($schoolId, GradingScale::DEFAULT);
            }
            return $created;
        });
    }
}
