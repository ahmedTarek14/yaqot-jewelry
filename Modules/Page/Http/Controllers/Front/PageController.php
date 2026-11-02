<?php

namespace Modules\Page\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Support\PlainText;
use App\Support\SeoMeta;
use App\Support\SiteContact;
use App\Support\WhatsApp;
use Illuminate\View\View;
use Modules\Analytics\Support\GoogleAnalytics;
use Modules\Branch\Models\Branch;

/**
 * About, contact and privacy policy. Everything comes from Settings and Branches; whatever is
 * not configured is simply hidden, and a page with nothing to show renders a calm empty state.
 */
class PageController extends Controller
{
    /**
     * The privacy policy's sections, in order, as lang keys under "page::privacy". `analytics`
     * (Google Analytics) and the `ga` variants of cookies/third_party replace the plain ones only
     * while a Google Analytics id is set, so the text never claims "no cookies / no analytics"
     * when that is not true. `counters` (the in-house product counters) is always described.
     */
    private const PRIVACY_SECTIONS = [
        'sections.no_accounts',
        'sections.search',
        'cookies',
        'third_party',
        'analytics',
        'sections.counters',
        'sections.contact_links',
        'sections.external_links',
        'sections.logs',
    ];

    public function about(SiteContact $contact): View
    {
        return view('page::front.about', [
            'metaDescription' => SeoMeta::describe(setting('about_content')),
            'paragraphs' => PlainText::paragraphs(setting('about_content')),
            'whatsappUrl' => $contact->whatsappUrl(),
            'phone' => $contact->phone(),
            'contactUrl' => route('front.contact'),
        ]);
    }

    public function contact(SiteContact $contact): View
    {
        $message = __('front.whatsapp.general_message');

        $branches = Branch::query()->active()->ordered()->with('translations')->get()
            ->map(fn (Branch $branch) => [
                'name' => $branch->name,
                'address' => $branch->address,
                'hours' => $branch->working_hours,
                'phone' => $branch->phone_url ? $branch->phone : null,
                'phoneUrl' => $branch->phone_url,
                'whatsappUrl' => WhatsApp::link($branch->whatsapp, $message),
                // The model refuses anything but https; checked again because this becomes a link.
                'mapUrl' => $this->httpsUrl($branch->map_url),
            ]);

        $whatsappUrl = $contact->whatsappUrl();
        $phone = $contact->phone();
        $email = $contact->email();
        $socials = $contact->socials();

        return view('page::front.contact', [
            'metaDescription' => SeoMeta::describe(__('page::contact.lead')),
            'whatsappUrl' => $whatsappUrl,
            'phone' => $phone,
            'email' => $email,
            'socials' => $socials,
            'branches' => $branches,
            'hasDirect' => $whatsappUrl !== null || $phone !== null || $email !== null || $socials !== [],
        ]);
    }

    public function privacy(SiteContact $contact): View
    {
        $analytics = GoogleAnalytics::enabled();

        return view('page::front.privacy', [
            'metaDescription' => SeoMeta::describe(__('page::privacy.lead')),
            'sections' => $this->privacySections($analytics),
            'analytics' => $analytics,
            'email' => $contact->email(),
        ]);
    }

    /**
     * @return array<int, string> lang keys under "page::privacy" that have a title and a text
     */
    private function privacySections(bool $analytics): array
    {
        $sections = [];

        foreach (self::PRIVACY_SECTIONS as $section) {
            if (str_starts_with($section, 'sections.')) {
                $sections[] = $section;
            } elseif ($analytics) {
                $sections[] = "sections_ga.$section";
            } elseif ($section !== 'analytics') {
                $sections[] = "sections.$section";
            }
        }

        return $sections;
    }

    private function httpsUrl(?string $url): ?string
    {
        return is_string($url) && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? $url : null;
    }
}
