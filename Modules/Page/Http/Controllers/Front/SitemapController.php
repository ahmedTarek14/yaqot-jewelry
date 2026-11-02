<?php

namespace Modules\Page\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\Page\Support\Sitemap;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * /sitemap.xml and /robots.txt. Both live outside the language prefix, because crawlers ask for
 * them at the site root, and both are served without a session or cookies.
 */
class SitemapController extends Controller
{
    public function sitemap(Sitemap $sitemap): StreamedResponse
    {
        return response()->stream(function () use ($sitemap) {
            foreach ($sitemap->xml() as $chunk) {
                echo $chunk;
            }
        }, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Only production is open to crawlers: a staging copy or a forgotten test site stays out of
     * the index. The WhatsApp redirect is no page, and the admin carries its own noindex.
     */
    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /ar/go/', 'Disallow: /en/go/', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
