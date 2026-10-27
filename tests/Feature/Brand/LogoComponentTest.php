<?php

namespace Tests\Feature\Brand;

use App\View\Components\Logo;
use ReflectionProperty;
use Tests\TestCase;

class LogoComponentTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->setManifest(null);

        parent::tearDown();
    }

    public function test_it_renders_the_generated_webp_with_dimensions_and_a_translated_alt(): void
    {
        app()->setLocale('ar');

        $html = (string) $this->blade('<x-logo size="header" />');
        $manifest = json_decode(file_get_contents(public_path(Logo::GENERATED_DIR.'/manifest.json')), true);
        $file = $manifest['files']['logo-h56.webp'];

        $this->assertStringContainsString('/assets/images/brand/generated/logo-h56.webp', $html);
        $this->assertStringContainsString('logo-h112.webp', $html);
        $this->assertStringContainsString(' 2x', $html);
        $this->assertStringContainsString('height="56"', $html);
        $this->assertStringContainsString('width="'.round($file['width'] * 56 / $file['height']).'"', $html);
        $this->assertStringContainsString('alt="ياقوت"', $html);
        $this->assertStringNotContainsString('loading="lazy"', $html);
    }

    public function test_the_footer_logo_is_lazy_loaded(): void
    {
        $this->assertStringContainsString('loading="lazy"', (string) $this->blade('<x-logo size="footer" />'));
    }

    public function test_it_falls_back_to_the_source_file_when_assets_are_not_generated(): void
    {
        $this->setManifest([]);
        [$width, $height] = getimagesize(public_path('assets/images/brand/logo.png'));

        $html = (string) $this->blade('<x-logo size="sidebar" />');

        $this->assertStringContainsString('/assets/images/brand/logo.png', $html);
        $this->assertStringNotContainsString('srcset', $html);
        $this->assertStringContainsString('height="64"', $html);
        $this->assertStringContainsString('width="'.round($width * 64 / $height).'"', $html);
    }

    public function test_an_unknown_size_falls_back_to_the_header_size(): void
    {
        $this->assertStringContainsString('height="56"', (string) $this->blade('<x-logo size="huge" />'));
    }

    private function setManifest(?array $manifest): void
    {
        (new ReflectionProperty(Logo::class, 'manifest'))->setValue(null, $manifest);
    }
}
