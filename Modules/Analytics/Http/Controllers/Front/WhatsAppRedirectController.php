<?php

namespace Modules\Analytics\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Modules\Analytics\Support\ProductStats;
use Modules\Product\Models\Product;

/**
 * The product page's WhatsApp button points here: the click is counted (bots are not), then the
 * visitor is sent on to the same wa.me link the page used to carry. The link is built on the
 * server, in the locale of the URL, so the message and the product page URL match that language.
 */
class WhatsAppRedirectController extends Controller
{
    public function __invoke(string $slug, WhatsApp $whatsapp, ProductStats $stats): RedirectResponse
    {
        $product = Product::query()->visible()->where('slug', $slug)->with('translations')->firstOrFail();

        $url = $whatsapp->productUrl($product->name, $product->sku, route('front.products.show', $product->slug));

        // No (valid) store number configured: nothing to open and nothing to count.
        abort_if($url === null || ! str_starts_with($url, 'https://wa.me/'), 404);

        $stats->recordWhatsAppClick($product);

        return redirect()->away($url, 302)->withHeaders([
            'Cache-Control' => 'no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
