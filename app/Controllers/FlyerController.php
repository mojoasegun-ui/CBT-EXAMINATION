<?php
declare(strict_types=1);

namespace ExamCore\Controllers;

use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\TenantContext;
use ExamCore\Services\ResultService;

/**
 * Printable marketing flyers with a QR code.
 *  - Owner Console: QR to the free practice CBT (grow the platform).
 *  - School portal: QR to that school's student sign-in (and free practice).
 */
final class FlyerController extends PortalController
{
    public const LAYOUTS = ['a4' => 'A4 poster', 'a5' => 'A5 — 2 per page', 'cards' => 'Hand-out cards — 8 per page'];

    public function console(Request $request): Response
    {
        $target = url('/?source=flyer');
        return $this->flyer($request, [
            'area' => 'console', 'school' => null, 'target' => $target,
            'headline' => 'Practise WAEC, NECO & JAMB CBT for free',
            'sub' => 'Real exam-style questions, a timer like the real CBT centre, and your score with explanations. No sign-up needed.',
            'steps' => ['Scan the QR code with your phone camera', 'Choose your exam, subject and year', 'Start practising, it is free'],
        ]);
    }

    public function school(Request $request): Response
    {
        $school = $this->service(TenantContext::class)->school();
        $target = url('/s/' . $school['slug']);
        return $this->flyer($request, [
            'area' => 'school', 'school' => $school, 'target' => $target,
            'headline' => 'Write your exams on computer — ' . $school['name'],
            'sub' => 'Students of ' . $school['name'] . ': sign in to see your exams, write CBT tests and check your results. Anyone can also practise WAEC, NECO & JAMB questions free.',
            'steps' => ['Scan the QR code', 'Sign in with your admission number and password', 'Write your exam and see your result'],
        ]);
    }

    private function flyer(Request $request, array $data): Response
    {
        $layout = (string) $request->query('layout', 'a4');
        $data['layout'] = array_key_exists($layout, self::LAYOUTS) ? $layout : 'a4';
        $data['contact'] = mb_substr(trim((string) $request->query('contact', '')), 0, 80);
        $data['headline'] = mb_substr(trim((string) $request->query('headline', '')), 0, 90) ?: $data['headline'];
        $data['qr'] = ResultService::qr($data['target']);
        $data['title'] = 'Flyer';
        $data['back'] = url($data['area'] === 'console' ? '/console' : '/school');
        return $this->page($request, 'shared/flyer', $data, 'layouts/print');
    }
}
