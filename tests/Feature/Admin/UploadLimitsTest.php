<?php

namespace Tests\Feature\Admin;

use App\Support\UploadLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\Admin;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The server's real upload limits (read from ini at runtime) are shown in the admin, and passed to
 * admin.js, which refuses a save that cannot fit instead of letting PHP drop the request.
 */
class UploadLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_limits_are_read_from_ini_not_assumed(): void
    {
        $this->assertSame(ini_parse_quantity((string) ini_get('post_max_size')), UploadLimits::postMaxBytes());
        $this->assertSame(ini_parse_quantity((string) ini_get('upload_max_filesize')), UploadLimits::fileMaxBytes());
    }

    public function test_sizes_are_formatted_in_megabytes_in_the_current_language(): void
    {
        $this->assertSame('20 MB', UploadLimits::format(20 * 1048576));
        $this->assertSame('2.5 MB', UploadLimits::format((int) (2.5 * 1048576)));
        $this->assertSame('no limit', UploadLimits::format(0));

        app()->setLocale('ar');
        $this->assertSame('20 م.ب', UploadLimits::format(20 * 1048576));
    }

    public function test_a_file_limit_above_the_request_limit_is_capped_by_it(): void
    {
        // upload_max_filesize can never be reached when the whole request may only be 8M.
        $capped = UploadLimits::summaryFor(8 * 1048576, 64 * 1048576);
        $this->assertSame(8 * 1048576, $capped['file']);
        $this->assertSame('8 MB', $capped['fileLabel']);

        // A smaller file limit stays; "no limit" (0) on the request leaves the file limit alone.
        $this->assertSame(2 * 1048576, UploadLimits::summaryFor(20 * 1048576, 2 * 1048576)['file']);
        $this->assertSame(10 * 1048576, UploadLimits::summaryFor(0, 10 * 1048576)['file']);
        $this->assertSame(8 * 1048576, UploadLimits::summaryFor(8 * 1048576, 0)['file']);
    }

    /** @return array<string, array{string}> */
    public static function locales(): array
    {
        return ['arabic' => ['ar'], 'english' => ['en']];
    }

    #[DataProvider('locales')]
    public function test_the_dashboard_the_product_form_and_the_scripts_show_the_real_limits(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        $this->actingAs(Admin::factory()->create(), 'admin');

        $summary = UploadLimits::summary();
        $text = __('admin.limits.dashboard_text', ['post' => $summary['postLabel'], 'file' => $summary['fileLabel']], $locale);
        $hint = __('admin.limits.server', ['post' => $summary['postLabel'], 'file' => $summary['fileLabel']], $locale);

        $this->get("/$locale/admin")->assertOk()->assertSee($text);

        $html = $this->get("/$locale/admin/products/create")->assertOk()->getContent();
        $this->assertStringContainsString(e($hint), $html);

        // admin.js gets the numbers it enforces, and the messages to show.
        preg_match('#<script id="yq-admin-i18n" type="application/json">(.*?)</script>#s', $html, $match);
        $messages = json_decode(html_entity_decode($match[1]), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($summary['post'], $messages['uploadLimits']['post']);
        $this->assertSame($summary['file'], $messages['uploadLimits']['file']);
        $this->assertArrayHasKey('postTooBig', $messages);
        $this->assertArrayHasKey('fileTooBig', $messages);
    }
}
