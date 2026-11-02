<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The shared body of the 404, 419, 429, 500 and 503 pages (resources/views/errors).
 *
 * It needs nothing but lang files and static assets: no database, no settings, no site layout,
 * so an error page can still render when the database is the thing that failed. Both languages
 * are shown, because the visitor's language is unknown on an unknown URL or during an outage;
 * the one in the URL's prefix comes first (English otherwise, the site's default). It prints
 * nothing about the error itself: no message, no trace, no debug data.
 */
class ErrorPage extends Component
{
    /** @var list<array{locale: string, dir: string, title: string, message: string, home: string}> */
    public array $blocks = [];

    public string $homeUrl;

    public function __construct(public string $code)
    {
        $first = request()->segment(1) === 'ar' ? 'ar' : 'en';
        $this->homeUrl = url('/'.$first);

        foreach ($first === 'ar' ? ['ar', 'en'] : ['en', 'ar'] as $locale) {
            $this->blocks[] = [
                'locale' => $locale,
                'dir' => $locale === 'ar' ? 'rtl' : 'ltr',
                'title' => __("errors.codes.$code.title", [], $locale),
                'message' => __("errors.codes.$code.message", [], $locale),
                'home' => __('errors.home', [], $locale),
            ];
        }
    }

    public function render(): View
    {
        return view('components.error-page');
    }
}
