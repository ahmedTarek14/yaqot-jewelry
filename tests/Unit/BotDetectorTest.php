<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Modules\Analytics\Support\BotDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BotDetectorTest extends TestCase
{
    /** @return array<string, array{?string}> */
    public static function bots(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'spaces' => ['   '],
            'googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            'bingbot' => ['Mozilla/5.0 (compatible; bingbot/2.0)'],
            'yahoo slurp' => ['Mozilla/5.0 (compatible; Yahoo! Slurp)'],
            'baidu spider' => ['Baiduspider/2.0'],
            'semrush crawler' => ['Mozilla/5.0 (compatible; SemrushCrawler)'],
            'bing preview' => ['Mozilla/5.0 BingPreview/1.0b'],
            'facebook' => ['facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'],
            'whatsapp preview' => ['WhatsApp/2.23.20.0 A'],
            'curl' => ['curl/8.4.0'],
            'wget' => ['Wget/1.21.4'],
            'python' => ['python-requests/2.31.0'],
            'headless' => ['Mozilla/5.0 (X11; Linux x86_64) HeadlessChrome/119.0.0.0 Safari/537.36'],
            'lighthouse' => ['Mozilla/5.0 (Linux; Android 11) Chrome-Lighthouse'],
            'upper case' => ['GOOGLEBOT'],
        ];
    }

    #[DataProvider('bots')]
    public function test_automated_agents_are_detected(?string $userAgent): void
    {
        $this->assertTrue(BotDetector::isBot($userAgent));
    }

    /** @return array<string, array{string}> */
    public static function browsers(): array
    {
        return [
            'chrome windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36'],
            'safari iphone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1'],
            'firefox android' => ['Mozilla/5.0 (Android 13; Mobile; rv:120.0) Gecko/120.0 Firefox/120.0'],
            'edge' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36 Edg/119.0.0.0'],
        ];
    }

    #[DataProvider('browsers')]
    public function test_real_browsers_are_not_bots(string $userAgent): void
    {
        $this->assertFalse(BotDetector::isBot($userAgent));
    }

    public function test_head_and_prefetch_are_not_visits(): void
    {
        $server = ['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/119.0 Safari/537.36'];

        $this->assertTrue(BotDetector::countsRequest(Request::create('/', 'GET', server: $server)));
        $this->assertFalse(BotDetector::countsRequest(Request::create('/', 'HEAD', server: $server)));
        $this->assertFalse(BotDetector::countsRequest(Request::create('/', 'GET', server: $server + ['HTTP_PURPOSE' => 'prefetch'])));
        $this->assertFalse(BotDetector::countsRequest(Request::create('/', 'GET', server: $server + ['HTTP_SEC_PURPOSE' => 'prefetch'])));
        $this->assertFalse(BotDetector::countsRequest(Request::create('/', 'GET', server: ['HTTP_USER_AGENT' => 'Googlebot/2.1'])));
    }

    public function test_the_token_list_comes_from_config(): void
    {
        config(['analytics.bot_tokens' => ['mycrawlerx']]);

        $this->assertTrue(BotDetector::isBot('Hello MyCrawlerX/1'));
        $this->assertFalse(BotDetector::isBot('Googlebot/2.1'));
    }
}
