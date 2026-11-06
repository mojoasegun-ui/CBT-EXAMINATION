<?php
declare(strict_types=1);

namespace ExamCore\Controllers\Console;

use ExamCore\Controllers\PortalController;
use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\View;
use ExamCore\Models\PlatformSettings;
use ExamCore\Services\AuditService;

/** Owner Console: rename the product and set the support contact. Everything else reads these values at runtime. */
final class BrandingController extends PortalController
{
    public function __construct(Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, private readonly PlatformSettings $settings, private readonly AuditService $audit)
    {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    public function form(Request $request): Response
    {
        $saved = $this->settings->all();
        return $this->page($request, 'console/branding', [
            'area' => 'console',
            'title' => 'Branding',
            'saved' => $saved,
            'defaults' => ['product_name' => PlatformSettings::DEFAULT_NAME, 'short_name' => PlatformSettings::DEFAULT_SHORT, 'tagline' => PlatformSettings::DEFAULT_TAGLINE],
        ]);
    }

    public function save(Request $request): Response
    {
        $input = [
            'product_name' => $request->input('product_name', ''),
            'short_name' => $request->input('short_name', ''),
            'tagline' => $request->input('tagline', ''),
            'support_email' => $request->input('support_email', ''),
        ];
        [$clean, $errors] = PlatformSettings::validate($input);
        if ($errors !== []) {
            return $this->backWithErrors('/console/branding', $errors, $clean, reset($errors));
        }
        // Typing the default name is the same as having none: nothing special is stored.
        if ($clean['product_name'] === PlatformSettings::DEFAULT_NAME) {
            $clean['product_name'] = '';
        }
        $this->settings->save($clean, $this->guard->id());
        $this->audit->record('platform.branding', 'Updated the product name and contact details', meta: array_filter($clean, static fn (string $v): bool => $v !== ''));
        return $this->redirectTo('/console/branding', 'Saved. The new name now appears across the site, emails and the installable app.');
    }

    public function reset(Request $request): Response
    {
        $this->settings->reset();
        $this->audit->record('platform.branding_reset', 'Restored the default product name and contact details');
        return $this->redirectTo('/console/branding', 'Restored the original name.', 'warning');
    }
}
