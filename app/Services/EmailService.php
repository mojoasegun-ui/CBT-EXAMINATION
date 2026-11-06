<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Mail\Mailer;

/** Composes platform emails. Delivery and retries are handled by Mailer and the job queue. */
final class EmailService
{
    public function __construct(private readonly Mailer $mailer, private readonly string $appName, private readonly float $passMarkPercent = 50.0)
    {
    }

    /**
     * @param array<string, mixed> $attempt
     * @param list<array<string, mixed>> $questions review rows (with correct_option and selected_option)
     */
    public function sendAttemptResults(array $attempt, array $questions): void
    {
        $email = trim((string) ($attempt['student_email'] ?? ''));
        if (!$this->mailer->enabled() || $email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        $student = trim((string) ($attempt['student_name'] ?? '')) ?: 'Student';
        $examTitle = (string) ($attempt['title'] ?? 'Practice exam');
        $score = (float) ($attempt['score'] ?? 0);
        $total = (float) ($attempt['total'] ?? 0);
        $percent = $total > 0 ? (int) round(($score / $total) * 100) : 0;
        $passed = $percent >= $this->passMarkPercent;

        $correct = $wrong = $unanswered = 0;
        $items = [];
        foreach ($questions as $index => $question) {
            $selected = strtoupper((string) ($question['selected_option'] ?? ''));
            $answer = strtoupper((string) ($question['correct_option'] ?? ''));
            if ($selected === '') {
                $unanswered++;
            } elseif ($selected === $answer) {
                $correct++;
                continue; // Only list the questions worth revisiting.
            } else {
                $wrong++;
            }
            $answerText = (string) ($question['option_' . strtolower($answer)] ?? '');
            $items[] = sprintf(
                '<li style="margin-bottom:10px;"><strong>Q%d.</strong> %s<br><span style="color:#64748b;">Your answer: %s · Correct: <strong>%s</strong> — %s</span></li>',
                $index + 1,
                e(mb_strimwidth((string) ($question['question_text'] ?? ''), 0, 160, '…')),
                $selected === '' ? 'No answer' : e($selected),
                e($answer),
                e($answerText),
            );
        }

        $statusText = $passed ? 'Pass' : 'Keep practising';
        $statusColor = $passed ? '#047857' : '#b45309';
        $review = $items === []
            ? '<p style="margin:0;color:#047857;font-weight:700;">Perfect score: every question correct. Excellent work!</p>'
            : '<ul style="margin:0;padding-left:20px;color:#334155;line-height:1.6;">' . implode('', $items) . '</ul>';
        $safeApp = e($this->appName);
        $safeStudent = e($student);
        $safeTitle = e($examTitle);
        $scoreText = e(rtrim(rtrim(number_format($score, 2), '0'), '.') . ' / ' . rtrim(rtrim(number_format($total, 2), '0'), '.'));

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Your result</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
  <div style="max-width:600px;margin:24px auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
    <div style="background:#1e40af;padding:20px 28px;color:#ffffff;font-weight:700;font-size:16px;">{$safeApp}</div>
    <div style="padding:28px;">
      <h1 style="margin:0 0 8px;font-size:22px;">Hello {$safeStudent},</h1>
      <p style="margin:0 0 20px;color:#334155;">Here is your result for <strong>{$safeTitle}</strong>.</p>
      <table role="presentation" width="100%" style="border-collapse:collapse;background:#eff6ff;border-radius:12px;">
        <tr>
          <td style="padding:18px 20px;"><div style="font-size:12px;color:#1d4ed8;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Score</div><div style="font-size:30px;font-weight:700;">{$percent}%</div><div style="color:#334155;">{$scoreText} marks</div></td>
          <td style="padding:18px 20px;text-align:right;"><span style="display:inline-block;padding:6px 12px;border-radius:999px;background:#ffffff;color:{$statusColor};font-weight:700;">{$statusText}</span></td>
        </tr>
      </table>
      <p style="margin:20px 0;color:#334155;"><strong>{$correct}</strong> correct · <strong>{$wrong}</strong> wrong · <strong>{$unanswered}</strong> unanswered</p>
      <h2 style="margin:0 0 12px;font-size:17px;">Questions to revisit</h2>
      {$review}
    </div>
    <div style="padding:16px 28px;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;">You received this because you entered this address before starting a practice exam on {$safeApp}.</div>
  </div>
</body></html>
HTML;

        $text = "Hello {$student},\n\nYour result for {$examTitle}: {$percent}% ({$statusText}).\n"
            . "{$correct} correct, {$wrong} wrong, {$unanswered} unanswered.\n\n— {$this->appName}";

        $this->mailer->send($email, $student, 'Your result: ' . $examTitle, $html, $text);
    }

    /**
     * Password recovery email: one button per school account behind the address.
     *
     * @param list<array{school:string,url:string}> $links
     */
    public function sendPasswordReset(string $to, string $name, array $links): void
    {
        if (!$this->mailer->enabled() || $links === [] || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }
        $app = e($this->appName);
        $hello = e($name !== '' ? explode(' ', trim($name))[0] : 'there');
        $buttons = '';
        $plain = '';
        foreach ($links as $link) {
            $school = e($link['school']);
            $url = e($link['url']);
            $buttons .= '<div style="margin:14px 0;"><div style="font-size:13px;color:#475569;margin-bottom:6px;">' . $school . '</div>'
                . '<a href="' . $url . '" style="display:inline-block;background:#1e40af;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:13px 26px;border-radius:12px;">Choose a new password</a></div>';
            $plain .= $link['school'] . ': ' . $link['url'] . "\n";
        }
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Reset your password</title></head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
  <div style="max-width:560px;margin:28px auto;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,.10);">
    <div style="background:linear-gradient(135deg,#1e3a8a,#1e40af 55%,#2563eb);padding:26px 30px;color:#ffffff;">
      <div style="font-size:19px;font-weight:700;">{$app}</div>
      <h1 style="margin:18px 0 4px;font-size:24px;line-height:1.25;">Reset your password</h1>
    </div>
    <div style="padding:28px 30px;">
      <p style="margin:0 0 12px;font-size:16px;line-height:1.7;color:#334155;">Hello {$hello}, we received a request to reset the password for your account. Use the button below. It works <strong>once</strong> and expires in <strong>one hour</strong>.</p>
      {$buttons}
      <p style="margin:22px 0 0;font-size:13px;line-height:1.6;color:#64748b;">If you did not ask for this, you can ignore this email. Your password stays the same until someone uses the link.</p>
    </div>
    <div style="padding:16px 30px;border-top:1px solid #e2e8f0;background:#f8fafc;color:#64748b;font-size:12px;text-align:center;">Sent by {$app}. Never share this email: anyone with the link can set a new password.</div>
  </div>
</body></html>
HTML;
        $this->mailer->send($to, $name, 'Reset your ' . $this->appName . ' password', $html, "Hello,\n\nUse this link to choose a new password (works once, expires in one hour):\n\n" . $plain . "\nIf you did not ask for this, ignore this email.\n\n" . $this->appName);
    }
}
