<?php

namespace Tests\Feature\Layout;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Branch\Models\Branch;
use Modules\Setting\Services\SettingService;
use Tests\TestCase;

/**
 * The public footer shows contact, social and branch details only when configured.
 */
class FooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unconfigured_footer_shows_only_links_and_copyright(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->get('/en')
            ->assertOk()
            ->assertSee('id="footer-explore"', false)
            ->assertDontSee('<h2 class="yq-footer__heading">Contact</h2>', false)
            ->assertDontSee('<h2 class="yq-footer__heading">Branches</h2>', false)
            ->assertDontSee('tel:', false)
            ->assertDontSee('mailto:', false)
            ->assertDontSee('yq-footer__text', false);
    }

    public function test_configured_contact_details_social_links_and_text_appear(): void
    {
        $this->refreshApplicationWithLocale('en');

        app(SettingService::class)->setMany([
            'whatsapp_number' => '201012345678',
            'phone' => '19123',
            'email' => 'hello@example.com',
            'instagram_url' => 'https://instagram.com/example',
            'footer_text' => ['ar' => 'نص', 'en' => "Demo footer line\n\nSecond demo line"],
        ]);

        $this->get('/en')
            ->assertOk()
            ->assertSee('<h2 class="yq-footer__heading">Contact</h2>', false)
            ->assertSee('href="tel:19123"', false)
            ->assertSee('href="mailto:hello@example.com"', false)
            ->assertSee('href="https://wa.me/201012345678?text=', false)
            ->assertSee('href="https://instagram.com/example"', false)
            ->assertDontSee('Facebook')
            ->assertSeeInOrder(['Demo footer line', 'Second demo line']);
    }

    public function test_active_branches_are_listed_with_their_phone(): void
    {
        $this->refreshApplicationWithLocale('ar');

        Branch::factory()->create(['phone' => '0223456789', 'ar' => ['name' => 'فرع تجريبي', 'address' => 'عنوان تجريبي']]);
        Branch::factory()->create(['is_active' => false, 'ar' => ['name' => 'فرع مغلق', 'address' => 'عنوان تجريبي']]);

        $this->get('/ar')
            ->assertOk()
            ->assertSee('<h2 class="yq-footer__heading">'.__('front.footer.branches', [], 'ar').'</h2>', false)
            ->assertSee('فرع تجريبي')
            ->assertSee('href="tel:+20223456789"', false)
            ->assertDontSee('فرع مغلق');
    }
}
