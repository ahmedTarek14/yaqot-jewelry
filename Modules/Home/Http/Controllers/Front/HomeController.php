<?php

namespace Modules\Home\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Support\PlainText;
use App\Support\SeoMeta;
use App\Support\SiteContact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Home\Support\HeroSlides;
use Modules\Product\Models\Product;
use Modules\Setting\Services\SettingService;

/**
 * The homepage. Every section is optional: the view hides a section whose list is
 * empty or whose settings are not filled in, so the page works on an empty site.
 */
class HomeController extends Controller
{
    /**
     * Brand names in each half of the marquee track (about 130px each at the least: wider than a 4K screen).
     */
    private const MARQUEE_MIN_ITEMS = 24;

    public function index(SettingService $settings, SiteContact $contact, HeroSlides $heroSlides): View
    {
        $limits = config('catalog.front.home');
        $catalogUrl = route('front.catalog');

        // Bars and coins have their own section: the other lists are jewelry only.
        $featured = Product::query()->visible()->jewelry()->featured()->forCard()->latestPublished()->limit($limits['featured'])->get();

        // The collection chosen in Settings comes first; the view shows the first tile large.
        $prominent = filter_var($settings->get('prominent_collection'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        $collections = Collection::query()->onSite()->onHomepage()->with('translations')
            ->when($prominent, fn (Builder $query) => $query->orderByRaw($query->qualifyColumn('id').' = ? desc', [$prominent]))
            ->ordered()->limit($limits['collections'])->get();

        $aboutParagraphs = PlainText::paragraphs($settings->get('about_content'));

        $slides = $heroSlides->slides($catalogUrl);
        $brands = Brand::query()->onSite()->ordered()->with('translations')->limit($limits['brands'])->get();

        return view('home::front.index', [
            'organization' => SeoMeta::organization($contact->phone()['label'] ?? null, $contact->email()['label'] ?? null, $contact->socials()),
            'catalogUrl' => $catalogUrl,
            'slides' => $slides,
            // One slide has no controls and no autoplay.
            'multiple' => count($slides) > 1,
            'categories' => Category::query()->onSite()->ordered()->with('translations')->limit($limits['categories'])->get(),
            'featured' => $featured,
            'collections' => $collections,
            'prominentCollection' => $prominent !== null && $collections->first()?->id === $prominent ? $collections->first() : null,
            'brands' => $brands,
            // The marquee needs each half of its track wider than any screen: repeat a short list.
            'brandRepeat' => max(1, intdiv(self::MARQUEE_MIN_ITEMS + max($brands->count(), 1) - 1, max($brands->count(), 1))),
            // Pieces already shown as featured are not repeated.
            'latest' => Product::query()->visible()->jewelry()->forCard()->latestPublished()
                ->whereKeyNot($featured->modelKeys())->limit($limits['latest'])->get(),
            'bullionUrl' => Route::has('front.bullion') ? route('front.bullion') : null,
            'bullion' => Route::has('front.bullion')
                ? Product::query()->visible()->bullion()->forCard()->latestPublished()->limit($limits['bullion'])->get()
                : new EloquentCollection,
            'about' => $aboutParagraphs[0] ?? null,
            'aboutUrl' => route('front.about'),
            'contact' => [
                'whatsappUrl' => $contact->whatsappUrl(),
                'phone' => $contact->phone(),
                'pageUrl' => route('front.contact'),
            ],
        ]);
    }
}
