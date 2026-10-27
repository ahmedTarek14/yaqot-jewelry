<?php

namespace Tests\Feature\Branch;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\Branch\Models\Branch;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BranchModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_factory_creates_both_translations(): void
    {
        $branch = Branch::factory()->create()->fresh();

        $this->assertCount(2, $branch->translations);
        $this->assertStringStartsWith('فرع تجريبي', $branch->translate('ar')->name);
        $this->assertStringStartsWith('Demo Branch', $branch->translate('en')->name);
        $this->assertNotEmpty($branch->translate('ar')->address);
        $this->assertNotEmpty($branch->translate('en')->address);
        $this->assertStringStartsWith('https://maps.example.com/', $branch->map_url);
        $this->assertMatchesRegularExpression('/^0100000\d{4}$/', $branch->phone);
        $this->assertTrue($branch->is_active);
        $this->assertSame(0, $branch->sort_order);
    }

    public function test_a_missing_arabic_translation_falls_back_to_english(): void
    {
        $branch = Branch::create([
            'phone' => '01000001234',
            'en' => ['name' => 'Demo Branch', 'address' => 'Demo Address'],
        ])->fresh();

        app()->setLocale('ar');

        $this->assertFalse($branch->hasTranslation('ar'));
        $this->assertSame('Demo Branch', $branch->name);
        $this->assertSame('Demo Address', $branch->address);
    }

    public function test_optional_attributes_are_nullable(): void
    {
        $branch = Branch::factory()->withoutOptional()->create()->fresh();

        $this->assertNull($branch->whatsapp);
        $this->assertNull($branch->map_url);
        $this->assertNull($branch->translate('ar')->working_hours);
        $this->assertNull($branch->translate('en')->working_hours);
        $this->assertNotEmpty($branch->translate('ar')->name);
        $this->assertNotEmpty($branch->translate('en')->address);
    }

    public function test_the_active_scope_excludes_inactive_branches(): void
    {
        $active = Branch::factory()->create();
        Branch::factory()->inactive()->create();

        $this->assertSame([$active->id], Branch::active()->pluck('id')->all());
    }

    public function test_the_ordered_scope_sorts_by_sort_order_then_id(): void
    {
        $third = Branch::factory()->create(['sort_order' => 2]);
        $first = Branch::factory()->create(['sort_order' => 1]);
        $second = Branch::factory()->create(['sort_order' => 1]);
        $zero = Branch::factory()->create(['sort_order' => 0]);

        $this->assertSame(
            [$zero->id, $first->id, $second->id, $third->id],
            Branch::ordered()->pluck('id')->all(),
        );
    }

    /** @return array<string, array{?string}> */
    public static function allowedMapUrls(): array
    {
        return [
            'https' => ['https://maps.example.com/demo'],
            'uppercase scheme' => ['HTTPS://maps.example.com/demo'],
            'null' => [null],
            'empty' => [''],
        ];
    }

    #[DataProvider('allowedMapUrls')]
    public function test_https_or_empty_map_urls_are_accepted(?string $url): void
    {
        $branch = Branch::factory()->create(['map_url' => $url]);

        $this->assertTrue($branch->exists);
    }

    /** @return array<string, array{string}> */
    public static function rejectedMapUrls(): array
    {
        return [
            'http' => ['http://maps.example.com/demo'],
            'javascript' => ['javascript:alert(1)'],
            'scheme-less' => ['maps.example.com/demo'],
            'protocol-relative' => ['//maps.example.com/demo'],
        ];
    }

    #[DataProvider('rejectedMapUrls')]
    public function test_non_https_map_urls_are_rejected(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);

        Branch::factory()->create(['map_url' => $url]);
    }

    public function test_updating_to_a_non_https_map_url_is_rejected(): void
    {
        $branch = Branch::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        $branch->update(['map_url' => 'http://maps.example.com/demo']);
    }

    public function test_eager_loaded_translations_work_with_lazy_loading_prevented(): void
    {
        Model::preventLazyLoading();
        Branch::factory()->count(3)->create();
        Branch::factory()->inactive()->create();

        $branches = Branch::with('translations')->active()->ordered()->get();

        $this->assertCount(3, $branches);
        foreach ($branches as $branch) {
            $this->assertStringStartsWith('Demo Branch', $branch->name);
        }
    }

    public function test_reading_translations_without_eager_loading_is_caught(): void
    {
        Model::preventLazyLoading();
        Branch::factory()->count(2)->create();

        $this->expectException(LazyLoadingViolationException::class);

        Branch::ordered()->get()->each(fn (Branch $branch) => $branch->name);
    }
}
