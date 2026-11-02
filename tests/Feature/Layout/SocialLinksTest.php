<?php

namespace Tests\Feature\Layout;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Social links are icons (inline SVG, no external request) with an accessible name in the
 * current language, and appear only for the networks the owner filled in.
 */
class SocialLinksTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function pages(): array
    {
        return [
            'footer en' => ['en', '/en'],
            'footer ar' => ['ar', '/ar'],
            'contact en' => ['en', '/en/contact'],
            'contact ar' => ['ar', '/ar/contact'],
        ];
    }

    #[DataProvider('pages')]
    public function test_each_configured_network_is_an_icon_link_with_a_localized_name(string $locale, string $url): void
    {
        $this->refreshApplicationWithLocale($locale);

        app(SettingService::class)->setMany([
            'facebook_url' => 'https://facebook.com/example',
            'instagram_url' => 'https://instagram.com/example',
            'tiktok_url' => 'https://tiktok.com/@example',
        ]);

        $html = $this->get($url)->assertOk()->getContent();

        foreach (['facebook', 'instagram', 'tiktok'] as $network) {
            $name = __("front.social.$network", [], $locale);
            $notice = __('front.contact.opens_new_tab', [], $locale);

            $this->assertMatchesRegularExpression(
                '#<a href="https://[^"]+" class="yq-social__link" target="_blank" rel="noopener">\s*<svg[^>]*aria-hidden="true"[^>]*>.*?</svg>\s*<span class="visually-hidden">'
                .preg_quote($name, '#').' '.preg_quote($notice, '#').'</span>\s*</a>#s',
                $html,
                "$network link on $url",
            );
        }

        // The visible text of the old text links is gone: the name only exists visually hidden.
        $this->assertStringNotContainsString('class="yq-underline" target="_blank"', $html);
    }

    #[DataProvider('pages')]
    public function test_a_network_that_is_not_filled_in_is_not_shown(string $locale, string $url): void
    {
        $this->refreshApplicationWithLocale($locale);

        app(SettingService::class)->setMany(['instagram_url' => 'https://instagram.com/example']);

        $html = $this->get($url)->assertOk()->getContent();

        // The contact page lists them in its body and again in the footer.
        $this->assertSame(str_contains($url, 'contact') ? 2 : 1, substr_count($html, 'class="yq-social__link"'));
        $this->assertStringContainsString(__('front.social.instagram', [], $locale), $html);
        $this->assertStringNotContainsString(__('front.social.facebook', [], $locale), $html);
        $this->assertStringNotContainsString(__('front.social.tiktok', [], $locale), $html);
    }

    #[DataProvider('pages')]
    public function test_without_any_link_there_is_no_social_list(string $locale, string $url): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->get($url)->assertOk()->assertDontSee('yq-social', false);
    }
}
