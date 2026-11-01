<?php

namespace Modules\Page\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Support\PlainText;
use App\Support\SiteContact;
use App\Support\WhatsApp;
use Illuminate\View\View;
use Modules\Branch\Models\Branch;

/**
 * About, contact and privacy policy. Everything comes from Settings and Branches; whatever is
 * not configured is simply hidden, and a page with nothing to show renders a calm empty state.
 */
class PageController extends Controller
{
    /** The privacy policy's sections, in order (lang/{locale}/page.php "privacy.sections"). */
    private const PRIVACY_SECTIONS = ['no_accounts', 'search', 'cookies', 'third_party', 'contact_links', 'external_links', 'logs'];

    public function about(SiteContact $contact): View
    {
        return view('page::front.about', [
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
        return view('page::front.privacy', [
            'sections' => self::PRIVACY_SECTIONS,
            'email' => $contact->email(),
        ]);
    }

    private function httpsUrl(?string $url): ?string
    {
        return is_string($url) && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? $url : null;
    }
}
