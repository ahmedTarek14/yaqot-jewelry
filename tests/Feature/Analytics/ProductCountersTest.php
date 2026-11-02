<?php

namespace Tests\Feature\Analytics;

use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Analytics\Models\ProductDailyStat;
use Modules\Analytics\Support\ProductStats;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The anonymous per-product daily counters: page views are counted by the product page,
 * once per visit, and only for real visits to products the public can see.
 */
class ProductCountersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv(LaravelLocalization::ENV_ROUTE_KEY) === false) {
            $this->refreshApplicationWithLocale('en');
        }
    }

    private function visible(): Product
    {
        return Product::factory()->visible()->create();
    }

    public function test_a_view_creates_one_row_and_a_second_visit_raises_it(): void
    {
        $this->travelTo('2026-11-02 10:00:00');
        $product = $this->visible();

        $this->get("/en/products/{$product->slug}")->assertOk();
        $row = ProductDailyStat::sole();
        $this->assertSame(1, $row->views);
        $this->assertSame(0, $row->whatsapp_clicks);
        $this->assertSame('2026-11-02', $row->date->toDateString());

        $this->get("/en/products/{$product->slug}")->assertOk();
        $this->assertSame(1, ProductDailyStat::count());
        $this->assertSame(2, ProductDailyStat::sole()->views);
    }

    public function test_each_day_gets_its_own_row(): void
    {
        $product = $this->visible();

        $this->travelTo('2026-11-02 23:30:00');
        $this->get("/en/products/{$product->slug}")->assertOk();
        $this->travelTo('2026-11-03 00:10:00');
        $this->get("/en/products/{$product->slug}")->assertOk();
        $this->get("/en/products/{$product->slug}")->assertOk();

        $rows = ProductDailyStat::query()->orderBy('date')->get();
        $this->assertCount(2, $rows);
        $this->assertSame([1, 2], $rows->pluck('views')->all());
        $this->assertSame(['2026-11-02', '2026-11-03'], $rows->map(fn ($row) => $row->date->toDateString())->all());
    }

    public function test_views_are_counted_per_product_and_per_locale_page(): void
    {
        $first = $this->visible();
        $second = $this->visible();

        $this->get("/en/products/{$first->slug}")->assertOk();
        $this->get("/en/products/{$first->slug}")->assertOk();
        $this->get("/en/products/{$second->slug}")->assertOk();

        $this->assertSame(2, ProductDailyStat::where('product_id', $first->id)->sole()->views);
        $this->assertSame(1, ProductDailyStat::where('product_id', $second->id)->sole()->views);
    }

    public function test_the_arabic_page_counts_too(): void
    {
        $this->refreshApplicationWithLocale('ar');
        $product = $this->visible();

        $this->get("/ar/products/{$product->slug}")->assertOk();

        $this->assertSame(1, ProductDailyStat::sole()->views);
    }

    /** @return array<string, array{string}> */
    public static function automatedAgents(): array
    {
        return [
            'googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            'bingpreview' => ['Mozilla/5.0 BingPreview/1.0b'],
            'facebook preview' => ['facebookexternalhit/1.1'],
            'whatsapp preview' => ['WhatsApp/2.23.20.0 A'],
            'curl' => ['curl/8.4.0'],
            'headless chrome' => ['Mozilla/5.0 HeadlessChrome/119.0 Safari/537.36'],
            'python' => ['python-requests/2.31.0'],
            'lighthouse' => ['Mozilla/5.0 Chrome-Lighthouse'],
            'empty' => [''],
        ];
    }

    #[DataProvider('automatedAgents')]
    public function test_automated_visits_are_not_counted(string $userAgent): void
    {
        $product = $this->visible();

        $this->withHeaders(['User-Agent' => $userAgent])->get("/en/products/{$product->slug}")->assertOk();

        $this->assertSame(0, ProductDailyStat::count());
    }

    public function test_head_and_prefetch_requests_are_not_counted(): void
    {
        $product = $this->visible();

        $this->call('HEAD', "/en/products/{$product->slug}");
        $this->withHeaders(['Purpose' => 'prefetch'])->get("/en/products/{$product->slug}")->assertOk();
        $this->withHeaders(['Sec-Purpose' => 'prefetch;prerender'])->get("/en/products/{$product->slug}")->assertOk();

        $this->assertSame(0, ProductDailyStat::count());

        // A normal browser visit still counts (headers set with withHeaders() stay until flushed).
        $this->flushHeaders()->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/119.0 Safari/537.36'])
            ->get("/en/products/{$product->slug}")->assertOk();
        $this->assertSame(1, ProductDailyStat::sole()->views);
    }

    public function test_products_the_public_cannot_see_are_never_counted(): void
    {
        $draft = Product::factory()->withImages()->create();
        $unpublished = Product::factory()->withImages()->create(['is_published' => false]);
        $trashed = $this->visible();
        $trashed->delete();
        $noImage = Product::factory()->published()->create();

        foreach ([$draft, $unpublished, $trashed, $noImage] as $product) {
            $this->get("/en/products/{$product->slug}")->assertNotFound();
        }
        $this->get('/en/products/does-not-exist')->assertNotFound();

        $this->assertSame(0, ProductDailyStat::count());
    }

    public function test_only_counters_are_stored_and_no_visitor_data(): void
    {
        $this->assertSame(
            ['id', 'product_id', 'date', 'views', 'whatsapp_clicks'],
            Schema::getColumnListing('product_daily_stats'),
        );

        $product = $this->visible();
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 Secret-Browser', 'X-Forwarded-For' => '203.0.113.7'])
            ->get("/en/products/{$product->slug}")->assertOk();

        $this->assertStringNotContainsString('203.0.113.7', json_encode(DB::table('product_daily_stats')->get()));
        $this->assertStringNotContainsString('Secret-Browser', json_encode(DB::table('product_daily_stats')->get()));
    }

    public function test_counting_sets_no_cookie(): void
    {
        $product = $this->visible();

        $response = $this->get("/en/products/{$product->slug}")->assertOk();

        foreach ($response->headers->getCookies() as $cookie) {
            $this->assertStringNotContainsString('yq_', $cookie->getName());
        }
    }

    public function test_increments_are_a_single_atomic_upsert(): void
    {
        $product = $this->visible();
        $stats = new ProductStats(Request::create('/', 'GET', server: ['HTTP_USER_AGENT' => 'Mozilla/5.0 Safari/537.36']));

        DB::enableQueryLog();
        $stats->recordView($product);
        $stats->recordView($product);
        $stats->recordWhatsAppClick($product);
        DB::disableQueryLog();

        $row = ProductDailyStat::sole();
        $this->assertSame(2, $row->views);
        $this->assertSame(1, $row->whatsapp_clicks);

        // Exactly one statement per recorded event: no select, no separate update.
        $queries = array_column(DB::getQueryLog(), 'query');
        $this->assertCount(3, $queries);
        foreach ($queries as $sql) {
            $this->assertStringStartsWith('insert into "product_daily_stats"', $sql);
        }
        $this->assertStringContainsString('on conflict ("product_id", "date") do update set "views" = views + 1', $queries[0]);
        $this->assertStringContainsString('on conflict ("product_id", "date") do update set "whatsapp_clicks" = whatsapp_clicks + 1', $queries[2]);
    }

    public function test_the_same_upsert_compiles_to_valid_mysql(): void
    {
        $connection = DB::connection();
        $grammar = new MySqlGrammar($connection);

        $sql = $grammar->compileUpsert(
            $connection->table('product_daily_stats'),
            [['date' => '2026-11-02', 'product_id' => 1, 'views' => 1, 'whatsapp_clicks' => 0]],
            ['product_id', 'date'],
            ['views' => DB::raw('views + 1')],
        );

        $this->assertSame(
            'insert into `product_daily_stats` (`date`, `product_id`, `views`, `whatsapp_clicks`) values (?, ?, ?, ?) on duplicate key update `views` = views + 1',
            $sql,
        );
    }
}
