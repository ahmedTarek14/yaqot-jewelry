<?php

namespace Tests\Feature\Support;

use App\Support\SiteContact;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Setting\Services\SettingService;
use Tests\TestCase;

/**
 * setting(), the configured WhatsApp number and messages, and SiteContact.
 */
class SiteHelpersTest extends TestCase
{
    use RefreshDatabase;

    private function settings(): SettingService
    {
        return app(SettingService::class);
    }

    public function test_the_setting_helper_reads_the_settings_service(): void
    {
        $this->assertNull(setting('phone'));
        $this->assertSame('fallback', setting('phone', 'fallback'));

        $this->settings()->setMany(['phone' => '0223456789', 'footer_text' => ['ar' => 'عنوان', 'en' => 'Title']]);

        $this->assertSame('0223456789', setting('phone'));
        $this->assertSame('Title', setting('footer_text'));

        app()->setLocale('ar');
        $this->assertSame('عنوان', setting('footer_text'));
    }

    public function test_whatsapp_is_not_configured_without_a_valid_number(): void
    {
        $whatsApp = app(WhatsApp::class);

        $this->assertFalse($whatsApp->isConfigured());
        $this->assertNull($whatsApp->chatUrl());
        $this->assertNull($whatsApp->productUrl('Ring', 'DEMO-1', 'https://example.com'));

        // A value saved before validation existed (or edited in the database) is ignored, not linked.
        $this->settings()->set('whatsapp_number', '0223456789');
        $this->assertNull(app(WhatsApp::class)->chatUrl());
    }

    public function test_the_product_message_names_the_piece_in_the_current_locale(): void
    {
        $this->settings()->set('whatsapp_number', '201012345678');

        $url = app(WhatsApp::class)->productUrl('Demo Ring', 'DEMO-000001', 'https://example.com/en/products/demo-ring');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://wa.me/201012345678?text=', $url);
        $this->assertSame(__('front.whatsapp.product_message', [
            'name' => 'Demo Ring', 'sku' => 'DEMO-000001', 'url' => 'https://example.com/en/products/demo-ring',
        ]), $query['text']);
        $this->assertStringContainsString('Demo Ring', $query['text']);
        $this->assertStringContainsString('DEMO-000001', $query['text']);
        $this->assertStringContainsString('https://example.com/en/products/demo-ring', $query['text']);

        app()->setLocale('ar');
        $url = app(WhatsApp::class)->productUrl('خاتم', 'DEMO-000001', 'https://example.com/ar/products/demo-ring');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertStringContainsString('خاتم', $query['text']);
        $this->assertStringContainsString('ياقوت', $query['text']);
    }

    public function test_site_contact_is_empty_on_an_empty_site(): void
    {
        $contact = app(SiteContact::class);

        $this->assertNull($contact->whatsappUrl());
        $this->assertNull($contact->phone());
        $this->assertNull($contact->email());
        $this->assertSame([], $contact->socials());
    }

    public function test_site_contact_builds_links_from_settings(): void
    {
        $this->settings()->setMany([
            'whatsapp_number' => '201012345678',
            'phone' => '0223456789',
            'email' => 'hello@example.com',
            'facebook_url' => 'https://facebook.com/example',
            'instagram_url' => 'http://instagram.com/insecure',
            'tiktok_url' => 'javascript:alert(1)',
        ]);

        $contact = app(SiteContact::class);

        $this->assertStringStartsWith('https://wa.me/201012345678?text=', $contact->whatsappUrl());
        $this->assertSame(['label' => '0223456789', 'url' => 'tel:+20223456789'], $contact->phone());
        $this->assertSame(['label' => 'hello@example.com', 'url' => 'mailto:hello@example.com'], $contact->email());
        // Only https profiles are linked.
        $this->assertSame([
            ['network' => 'facebook', 'label' => 'Facebook', 'url' => 'https://facebook.com/example'],
        ], $contact->socials());
    }

    public function test_an_invalid_email_is_not_linked(): void
    {
        $this->settings()->set('email', 'not an email');

        $this->assertNull(app(SiteContact::class)->email());
    }
}
