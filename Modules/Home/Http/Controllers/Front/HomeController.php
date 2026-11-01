<?php

namespace Modules\Home\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Support\PlainText;
use App\Support\SiteContact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Product\Models\Product;
use Modules\Setting\Services\SettingService;

/**
 * The homepage. Every section is optional: the view hides a section whose list is
 * empty or whose settings are not filled in, so the page works on an empty site.
 */
class HomeController extends Controller
{
    public function index(SettingService $settings, SiteContact $contact): View
    {
        $limits = config('catalog.front.home');
        $catalogUrl = route('front.catalog');

        $featured = Product::query()->visible()->featured()->forCard()->latestPublished()->limit($limits['featured'])->get();

        // The collection chosen in Settings comes first; the view shows the first tile large.
        $prominent = filter_var($settings->get('prominent_collection'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        $collections = Collection::query()->onSite()->onHomepage()->with('translations')
            ->when($prominent, fn (Builder $query) => $query->orderByRaw($query->qualifyColumn('id').' = ? desc', [$prominent]))
            ->ordered()->limit($limits['collections'])->get();

        $aboutParagraphs = PlainText::paragraphs($settings->get('about_content'));
        $heroImage = $settings->get('hero_image');

        return view('home::front.index', [
            'catalogUrl' => $catalogUrl,
            'hero' => [
                'title' => $settings->get('hero_title', __('front.brand.name')),
                'subtitle' => $settings->get('hero_subtitle'),
                'image' => $heroImage ? Storage::disk('public')->url($heroImage) : null,
                'ctaLabel' => $settings->get('hero_cta_label', __('home::home.hero.cta')),
                'ctaUrl' => $settings->get('hero_cta_url', $catalogUrl),
            ],
            'categories' => Category::query()->onSite()->ordered()->with('translations')->limit($limits['categories'])->get(),
            'featured' => $featured,
            'collections' => $collections,
            'prominentCollection' => $prominent !== null && $collections->first()?->id === $prominent ? $collections->first() : null,
            'brands' => Brand::query()->onSite()->ordered()->with('translations')->limit($limits['brands'])->get(),
            // Pieces already shown as featured are not repeated.
            'latest' => Product::query()->visible()->forCard()->latestPublished()
                ->whereKeyNot($featured->modelKeys())->limit($limits['latest'])->get(),
            'about' => $aboutParagraphs[0] ?? null,
            'aboutUrl' => Route::has('front.about') ? route('front.about') : null,
            'contact' => [
                'whatsappUrl' => $contact->whatsappUrl(),
                'phone' => $contact->phone(),
                'pageUrl' => Route::has('front.contact') ? route('front.contact') : null,
            ],
        ]);
    }
}
