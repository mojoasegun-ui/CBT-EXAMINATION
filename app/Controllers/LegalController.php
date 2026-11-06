<?php
declare(strict_types=1);

namespace ExamCore\Controllers;

use ExamCore\Core\Request;
use ExamCore\Core\Response;

/** Public Privacy Notice and Terms of Use. Plain pages, no sign-in. */
final class LegalController extends PortalController
{
    private function contact(): string
    {
        $mail = (string) ($this->container->get('config')['app']['support_email'] ?? '') ?: (string) (env('SUPPORT_EMAIL') ?: env('MAIL_FROM_ADDRESS') ?: '');
        return filter_var($mail, FILTER_VALIDATE_EMAIL) !== false && !str_ends_with($mail, '.local') ? $mail : '';
    }

    public function privacy(Request $request): Response
    {
        return $this->page($request, 'legal/privacy', ['title' => 'Privacy notice', 'area' => 'school', 'contact' => $this->contact(), 'updated' => '6 November 2026'], 'layouts/doc');
    }

    public function terms(Request $request): Response
    {
        return $this->page($request, 'legal/terms', ['title' => 'Terms of use', 'area' => 'school', 'contact' => $this->contact(), 'updated' => '6 November 2026'], 'layouts/doc');
    }
}
