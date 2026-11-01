<?php

namespace App\View\Composers;

use App\Support\PlainText;
use App\Support\SiteContact;
use Illuminate\View\View;
use Modules\Branch\Models\Branch;
use Modules\Setting\Services\SettingService;

/**
 * The public footer's contact, social and branch details. Each part is empty
 * when nothing is configured, and the footer hides it.
 */
class FooterComposer
{
    /** Branches listed in the footer; the contact page lists them all. */
    public const BRANCHES = 4;

    public function __construct(
        private readonly SiteContact $contact,
        private readonly SettingService $settings,
    ) {}

    public function compose(View $view): void
    {
        $view->with('footer', [
            'text' => PlainText::paragraphs($this->settings->get('footer_text')),
            'phone' => $this->contact->phone(),
            'email' => $this->contact->email(),
            'whatsappUrl' => $this->contact->whatsappUrl(),
            'socials' => $this->contact->socials(),
            'branches' => Branch::active()->ordered()->with('translations')->limit(self::BRANCHES)->get(),
        ]);
    }
}
