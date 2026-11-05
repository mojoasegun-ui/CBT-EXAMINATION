<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * Data repair for the old "self-healing" question cloner (Question::repairForExam and
 * database/repair_exam_questions.php). That code padded empty papers by copying another
 * paper's questions, which put Mathematics questions inside Literature, Government, etc.
 *
 *  1. Any exam where at least half the questions are copies of an earlier exam's questions
 *     is deactivated (not deleted, so past attempts and their answers stay intact).
 *  2. Questions repeated inside the same exam are removed, keeping the first copy; answers
 *     pointing at the repeat are moved to the original, and the exam's total is corrected.
 *
 * Irreversible by design: restore storage/backups/pre-milestone1-*.sql if ever needed.
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $pdo = $schema->pdo();
        $q = $schema->table('questions');
        $e = $schema->table('exams');
        $a = $schema->table('attempt_answers');

        // The earliest row for each question text is treated as the original.
        $pdo->exec("CREATE TEMPORARY TABLE tmp_question_origin AS
            SELECT question_text, MIN(id) AS origin_id FROM {$q} GROUP BY question_text");

        $pdo->exec("UPDATE {$e} ex
            JOIN (
                SELECT q.exam_id,
                       SUM(origin.exam_id <> q.exam_id) AS copied,
                       COUNT(*) AS total
                FROM {$q} q
                JOIN tmp_question_origin o ON o.question_text = q.question_text
                JOIN {$q} origin ON origin.id = o.origin_id
                GROUP BY q.exam_id
            ) stats ON stats.exam_id = ex.id
            SET ex.is_active = 0
            WHERE stats.copied * 2 >= stats.total");

        // Repeats inside one exam: map each repeat to the first row with the same text in that exam.
        $pdo->exec("CREATE TEMPORARY TABLE tmp_in_exam_repeat AS
            SELECT q.id AS repeat_id, keep.keep_id
            FROM {$q} q
            JOIN (SELECT exam_id, question_text, MIN(id) AS keep_id FROM {$q} GROUP BY exam_id, question_text) keep
              ON keep.exam_id = q.exam_id AND keep.question_text = q.question_text
            WHERE q.id <> keep.keep_id");

        $pdo->exec("UPDATE IGNORE {$a} ans JOIN tmp_in_exam_repeat r ON r.repeat_id = ans.question_id SET ans.question_id = r.keep_id");
        $pdo->exec("DELETE ans FROM {$a} ans JOIN tmp_in_exam_repeat r ON r.repeat_id = ans.question_id");
        $pdo->exec("DELETE q FROM {$q} q JOIN tmp_in_exam_repeat r ON r.repeat_id = q.id");

        $pdo->exec("UPDATE {$e} ex
            JOIN (SELECT exam_id, COUNT(*) AS n FROM {$q} GROUP BY exam_id) c ON c.exam_id = ex.id
            SET ex.total_questions = c.n
            WHERE ex.total_questions <> c.n AND c.n > 0");

        $pdo->exec('DROP TEMPORARY TABLE IF EXISTS tmp_question_origin');
        $pdo->exec('DROP TEMPORARY TABLE IF EXISTS tmp_in_exam_repeat');
    }
};
