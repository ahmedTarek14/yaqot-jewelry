<?php

namespace Tests\Feature\Product;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\Admin;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Products admin: listing, filters, the form pages, saving attributes and pivots,
 * toggles, soft delete and restore. Images are covered in ProductAdminImagesTest.
 */
class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function locales(): array
    {
        return ['arabic' => ['ar'], 'english' => ['en']];
    }

    private function signIn(string $locale = 'en'): static
    {
        $this->refreshApplicationWithLocale($locale);

        return $this->actingAs(Admin::factory()->create(), 'admin');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name_ar' => 'خاتم تجريبي',
            'name_en' => 'Demo Ring',
            'description_ar' => 'وصف تجريبي.',
            'description_en' => 'A demo description.',
            'sku' => 'DEMO-RING-1',
            'brand_id' => '',
            'karat' => '21',
            'gold_color' => 'yellow',
            'stone_type' => 'diamond',
            'weight_grams' => '12.375',
            'is_published' => '1',
            'is_featured' => '0',
        ], $overrides);
    }

    /**
     * @param  array<string, string>  $names
     * @param  array<string, mixed>  $attributes
     */
    private function product(array $names, array $attributes = []): Product
    {
        return Product::factory()->create([
            ...$attributes,
            'en' => ['name' => $names['en']],
            'ar' => ['name' => $names['ar']],
        ]);
    }

    /**
     * @param  class-string<Brand|Category|Collection>  $model
     */
    private function named(string $model, string $en, string $ar = 'اسم تجريبي'): Brand|Category|Collection
    {
        return $model::factory()->create(['en' => ['name' => $en], 'ar' => ['name' => $ar]]);
    }

    /* ----------------------------------------------------------------- Index */

    #[DataProvider('locales')]
    public function test_the_index_lists_products_with_both_names_status_and_sku(string $locale): void
    {
        $this->signIn($locale);
        $published = $this->product(['en' => 'Demo Ring', 'ar' => 'خاتم تجريبي'], ['sku' => 'DEMO-R1', 'is_published' => true]);
        $draft = $this->product(['en' => 'Demo Anklet', 'ar' => 'خلخال تجريبي'], ['sku' => 'DEMO-A1', 'is_featured' => true]);

        $this->get("/$locale/admin/products")
            ->assertOk()
            ->assertSee('<html lang="'.$locale.'"', false)
            ->assertSee(__('product::product.title', [], $locale))
            ->assertSee('Demo Ring')
            ->assertSee('خاتم تجريبي')
            ->assertSee('Demo Anklet')
            ->assertSee('DEMO-R1')
            ->assertSee('DEMO-A1')
            ->assertSee(__('admin.status.published', [], $locale))
            ->assertSee(__('admin.status.draft', [], $locale))
            ->assertSee(__('product::product.featured.on', [], $locale))
            ->assertSee(__('product::product.featured.off', [], $locale))
            ->assertSee('data-toggle-url="'.route('admin.products.toggle-published', $draft).'"', false)
            ->assertSee('data-toggle-url="'.route('admin.products.toggle-featured', $published).'"', false)
            ->assertSee('data-url="'.route('admin.products.destroy', $published).'"', false)
            ->assertSee('href="'.route('admin.products.edit', $published).'"', false)
            ->assertSee('href="'.route('admin.products.create').'"', false);
    }

    public function test_the_index_shows_the_primary_image_without_lazy_loading(): void
    {
        $this->signIn();
        foreach (range(1, 3) as $i) {
            $product = $this->product(['en' => "Demo Piece $i", 'ar' => "قطعة $i"]);
            ProductImage::factory()->for($product)->create(['path' => "products/{$product->id}/second.webp", 'sort_order' => 1]);
            ProductImage::factory()->for($product)->create(['path' => "products/{$product->id}/first.webp", 'sort_order' => 0]);
        }

        // preventLazyLoading is on: a lazy load in a row would throw.
        $this->get('/en/admin/products')
            ->assertOk()
            ->assertSee("products/{$product->id}/first.webp")
            ->assertDontSee('second.webp');
    }

    public function test_the_index_searches_names_in_either_language_and_the_sku(): void
    {
        $this->signIn();
        $this->product(['en' => 'Demo Ring', 'ar' => 'خاتم تجريبي'], ['sku' => 'RNG-100']);
        $this->product(['en' => 'Demo Bracelet', 'ar' => 'أسورة تجريبية'], ['sku' => 'BRC-200']);

        $this->get('/en/admin/products?q=ring')->assertOk()->assertSee('Demo Ring')->assertDontSee('Demo Bracelet');
        $this->get('/en/admin/products?q='.urlencode('أسورة'))->assertOk()->assertSee('Demo Bracelet')->assertDontSee('Demo Ring');
        $this->get('/en/admin/products?q=brc-2')->assertOk()->assertSee('Demo Bracelet')->assertDontSee('Demo Ring');
        $this->get('/en/admin/products?q=_')->assertOk()->assertSee(__('admin.listing.empty_filtered'));
    }

    public function test_the_index_filters_by_status_and_featured(): void
    {
        $this->signIn();
        $this->product(['en' => 'Demo Published', 'ar' => 'منشور'], ['is_published' => true]);
        $this->product(['en' => 'Demo Draft', 'ar' => 'مسودة'], ['is_featured' => true]);

        $this->get('/en/admin/products?status=published')->assertSee('Demo Published')->assertDontSee('Demo Draft');
        $this->get('/en/admin/products?status=draft')->assertSee('Demo Draft')->assertDontSee('Demo Published');
        $this->get('/en/admin/products?featured=yes')->assertSee('Demo Draft')->assertDontSee('Demo Published');
        $this->get('/en/admin/products?featured=no')->assertSee('Demo Published')->assertDontSee('Demo Draft');
    }

    public function test_the_index_filters_by_category_collection_brand_and_karat(): void
    {
        $this->signIn();
        $category = $this->named(Category::class, 'Demo Category X');
        $collection = $this->named(Collection::class, 'Demo Bridal Collection');
        $brand = $this->named(Brand::class, 'Demo Brand');

        $ring = $this->product(['en' => 'Demo Ring', 'ar' => 'خاتم'], ['brand_id' => $brand->id, 'karat' => 18]);
        $ring->categories()->attach($category);
        $necklace = $this->product(['en' => 'Demo Necklace', 'ar' => 'عقد'], ['karat' => 21]);
        $necklace->collections()->attach($collection);

        $this->get("/en/admin/products?category={$category->id}")->assertSee('Demo Ring')->assertDontSee('Demo Necklace');
        $this->get("/en/admin/products?collection={$collection->id}")->assertSee('Demo Necklace')->assertDontSee('Demo Ring');
        $this->get("/en/admin/products?brand={$brand->id}")->assertSee('Demo Ring')->assertDontSee('Demo Necklace');
        $this->get('/en/admin/products?brand=none')->assertSee('Demo Necklace')->assertDontSee('Demo Ring');
        $this->get('/en/admin/products?karat=18')->assertSee('Demo Ring')->assertDontSee('Demo Necklace');
        $this->get('/en/admin/products?karat=21')->assertSee('Demo Necklace')->assertDontSee('Demo Ring');

        // The filter selects list the options by name, with the current one selected.
        $this->get("/en/admin/products?category={$category->id}")
            ->assertSee('<option value="'.$category->id.'" selected>Demo Category X</option>', false)
            ->assertSee('Demo Bridal Collection')
            ->assertSee('<option value="none" >'.__('product::product.filters.no_brand').'</option>', false)
            ->assertSee('<option value="24" >24K</option>', false);
    }

    public function test_the_delete_button_asks_to_move_the_product_to_the_trash(): void
    {
        $this->signIn('ar');
        $product = $this->product(['en' => 'Demo Ring', 'ar' => 'خاتم']);

        $this->get('/ar/admin/products')
            ->assertOk()
            ->assertSee('data-url="'.route('admin.products.destroy', $product).'"', false)
            ->assertSee('data-confirm="'.e(__('product::product.messages.trash_confirm', [], 'ar')).'"', false);
    }

    public function test_the_index_filters_the_trash(): void
    {
        $this->signIn();
        $this->product(['en' => 'Demo Kept', 'ar' => 'باقٍ']);
        $trashed = $this->product(['en' => 'Demo Trashed', 'ar' => 'محذوف']);
        $trashed->delete();

        $this->get('/en/admin/products')->assertSee('Demo Kept')->assertDontSee('Demo Trashed');

        $this->get('/en/admin/products?trashed=only')
            ->assertOk()
            ->assertSee('Demo Trashed')
            ->assertDontSee('Demo Kept')
            ->assertSee(__('admin.status.in_trash'))
            ->assertSee('action="'.route('admin.products.restore', $trashed).'"', false)
            ->assertDontSee('href="'.route('admin.products.edit', $trashed).'"', false)
            ->assertDontSee('data-url="'.route('admin.products.destroy', $trashed).'"', false)
            ->assertDontSee('data-toggle-url', false);

        $this->get('/en/admin/products?trashed=with')->assertSee('Demo Trashed')->assertSee('Demo Kept');
    }

    public function test_invalid_filter_and_sort_values_are_ignored(): void
    {
        $this->signIn();
        $this->product(['en' => 'Demo Ring', 'ar' => 'خاتم'], ['karat' => 18]);
        $trashed = $this->product(['en' => 'Demo Trashed', 'ar' => 'محذوف']);
        $trashed->delete();

        foreach (['status=deleted', 'category=999', 'collection=abc', 'brand=999', 'karat=18.0', 'karat=22',
            'featured=maybe', 'trashed=all', 'sort=slug', 'sort=id);drop table products;--', 'category[]=1'] as $query) {
            $this->get("/en/admin/products?$query")->assertOk()->assertSee('Demo Ring')->assertDontSee('Demo Trashed');
        }
    }

    public function test_the_index_sorts_by_allowlisted_columns(): void
    {
        $this->signIn();
        $this->product(['en' => 'Demo Bravo', 'ar' => 'ب'], ['sku' => 'C-1'])->forceFill(['updated_at' => now()->subDays(2)])->saveQuietly();
        $this->product(['en' => 'Demo Alpha', 'ar' => 'أ'], ['sku' => 'B-1'])->forceFill(['updated_at' => now()->subDay()])->saveQuietly();
        $this->product(['en' => 'Demo Charlie', 'ar' => 'ج'], ['sku' => 'A-1']);

        // Default: most recently updated first.
        $this->get('/en/admin/products')->assertSeeInOrder(['Demo Charlie', 'Demo Alpha', 'Demo Bravo']);
        // The sort links always carry dir; without it the page's default direction (desc) applies.
        $this->get('/en/admin/products?sort=name&dir=asc')->assertSeeInOrder(['Demo Alpha', 'Demo Bravo', 'Demo Charlie']);
        $this->get('/en/admin/products?sort=name&dir=desc')->assertSeeInOrder(['Demo Charlie', 'Demo Bravo', 'Demo Alpha']);
        $this->get('/en/admin/products?sort=sku&dir=asc')->assertSeeInOrder(['Demo Charlie', 'Demo Alpha', 'Demo Bravo']);
        $this->get('/en/admin/products?sort=updated_at&dir=asc')->assertSeeInOrder(['Demo Bravo', 'Demo Alpha', 'Demo Charlie']);
        $this->get('/en/admin/products?sort=slug&dir=asc')->assertOk()->assertSeeInOrder(['Demo Charlie', 'Demo Alpha', 'Demo Bravo']);
    }

    public function test_the_index_paginates_and_redirects_past_the_end(): void
    {
        $this->signIn();
        config(['catalog.admin.per_page' => 2]);
        foreach (range(1, 3) as $i) {
            $this->product(['en' => "Demo Item $i", 'ar' => "عنصر $i"], ['is_published' => true]);
        }

        $this->get('/en/admin/products?status=published')->assertOk()->assertSee('status=published&amp;page=2', false);
        $this->get('/en/admin/products?status=published&page=9')
            ->assertRedirect('http://localhost/en/admin/products?status=published&page=2');
    }

    #[DataProvider('locales')]
    public function test_the_index_has_empty_states(string $locale): void
    {
        $this->signIn($locale);

        $this->get("/$locale/admin/products")
            ->assertOk()
            ->assertSee(__('product::product.empty', [], $locale));

        $this->get("/$locale/admin/products?q=nothing")
            ->assertOk()
            ->assertSee(__('admin.listing.empty_filtered', [], $locale));
    }

    /* ------------------------------------------------------- Create and edit */

    #[DataProvider('locales')]
    public function test_the_create_page_has_every_section(string $locale): void
    {
        $this->signIn($locale);
        $brand = $this->named(Brand::class, 'Demo Brand', 'ماركة تجريبية');
        $category = $this->named(Category::class, 'Demo Rings', 'خواتم تجريبية');
        $this->named(Collection::class, 'Demo Bridal', 'مجموعة العرائس');
        $name = fn (string $en, string $ar) => $locale === 'ar' ? $ar : $en;

        $this->get("/$locale/admin/products/create")
            ->assertOk()
            ->assertSee(__('product::product.create', [], $locale))
            ->assertSee('class="ajax-form"', false)
            ->assertSee('action="'.route('admin.products.store').'"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertDontSee('name="_method" value="PUT"', false)
            ->assertSee('name="name_ar"', false)
            ->assertSee('name="description_en"', false)
            ->assertSee('name="sku"', false)
            ->assertSee('step="0.001"', false)
            ->assertSee('<option value="">'.__('product::product.fields.no_brand', [], $locale).'</option>', false)
            ->assertSee('<option value="'.$brand->id.'" >'.$name('Demo Brand', 'ماركة تجريبية').'</option>', false)
            ->assertSee('<option value="18" >'.__('product::product.karats.18', [], $locale).'</option>', false)
            ->assertSee('<option value="rose" >'.__('product::product.gold_colors.rose', [], $locale).'</option>', false)
            ->assertSee(__('product::product.stone_types.pearl', [], $locale))
            ->assertSee('name="categories[]" value="'.$category->id.'"', false)
            ->assertSee($name('Demo Bridal', 'مجموعة العرائس'))
            ->assertSee('name="images[]"', false)
            ->assertSee('data-gallery', false)
            ->assertSee('name="is_published"', false)
            ->assertSee('name="is_featured"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee(__('admin.actions.view_on_site', [], $locale))
            ->assertSee(__('admin.actions.view_on_site_soon', [], $locale));
    }

    #[DataProvider('locales')]
    public function test_the_edit_page_is_filled_in(string $locale): void
    {
        $this->signIn($locale);
        $brand = $this->named(Brand::class, 'Demo Brand');
        $chosen = $this->named(Category::class, 'Demo Chosen');
        $other = $this->named(Category::class, 'Demo Other');
        $collection = $this->named(Collection::class, 'Demo Bridal');
        $product = $this->product(['en' => 'Demo Ring', 'ar' => 'خاتم تجريبي'], [
            'sku' => 'DEMO-EDIT', 'brand_id' => $brand->id, 'karat' => 24, 'gold_color' => 'white',
            'stone_type' => 'pearl', 'weight_grams' => '7.250', 'is_featured' => true,
        ]);
        $product->categories()->attach($chosen);
        $product->collections()->attach($collection);
        $second = ProductImage::factory()->for($product)->create(['path' => "products/{$product->id}/b.webp", 'sort_order' => 1]);
        $first = ProductImage::factory()->for($product)->create(['path' => "products/{$product->id}/a.webp", 'sort_order' => 0]);

        $response = $this->get("/$locale/admin/products/{$product->id}/edit");
        $html = $response->getContent();

        $response->assertOk()
            ->assertSee(__('product::product.edit', [], $locale))
            ->assertSee('action="'.route('admin.products.update', $product).'"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('value="Demo Ring"', false)
            ->assertSee('value="خاتم تجريبي"', false)
            ->assertSee('value="DEMO-EDIT"', false)
            ->assertSee('value="7.250"', false)
            ->assertSee('<option value="'.$brand->id.'" selected>', false)
            ->assertSee('<option value="24" selected>', false)
            ->assertSee('<option value="white" selected>', false)
            ->assertSee('<option value="pearl" selected>', false)
            ->assertSeeInOrder(['value="id:'.$first->id.'"', 'value="id:'.$second->id.'"'], false);

        $this->assertMatchesRegularExpression('/value="'.$chosen->id.'"\s+id="field-categories-'.$chosen->id.'"\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="'.$other->id.'"\s+id="field-categories-'.$other->id.'"\s+checked/', $html);
        $this->assertMatchesRegularExpression('/value="'.$collection->id.'"\s+id="field-collections-'.$collection->id.'"\s+checked/', $html);
        $this->assertMatchesRegularExpression('/name="is_featured"\s+value="1" checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="is_published"\s+value="1" checked/', $html);
    }

    public function test_trashed_and_unknown_products_cannot_be_edited(): void
    {
        $this->signIn();
        $product = $this->product(['en' => 'Demo Ring', 'ar' => 'خاتم']);
        $product->delete();

        $this->get("/en/admin/products/{$product->id}/edit")->assertNotFound();
        $this->putJson("/en/admin/products/{$product->id}", $this->payload())->assertNotFound();
        $this->patchJson("/en/admin/products/{$product->id}/published")->assertNotFound();
        $this->delete("/en/admin/products/{$product->id}")->assertNotFound();
        $this->get('/en/admin/products/999/edit')->assertNotFound();
    }

    /* ----------------------------------------------------------------- Store */

    #[DataProvider('locales')]
    public function test_store_creates_the_product_with_translations_attributes_and_pivots(string $locale): void
    {
        $this->signIn($locale);
        $brand = $this->named(Brand::class, 'Demo Brand');
        $categories = Category::factory()->count(2)->create();
        $collection = $this->named(Collection::class, 'Demo Bridal');

        $this->postJson("/$locale/admin/products", $this->payload([
            'brand_id' => (string) $brand->id,
            'categories' => $categories->modelKeys(),
            'collections' => [$collection->id],
        ]))->assertOk()->assertExactJson([
            'message' => __('admin.messages.saved', [], $locale),
            'redirect' => "http://localhost/$locale/admin/products",
        ]);

        $product = Product::with(['translations', 'categories', 'collections'])->sole();
        $this->assertSame('Demo Ring', $product->translate('en')->name);
        $this->assertSame('خاتم تجريبي', $product->translate('ar')->name);
        $this->assertSame('وصف تجريبي.', $product->translate('ar')->description);
        $this->assertSame('demo-ring', $product->slug);
        $this->assertSame('DEMO-RING-1', $product->sku);
        $this->assertSame($brand->id, $product->brand_id);
        $this->assertSame(21, $product->karat);
        $this->assertSame('yellow', $product->gold_color);
        $this->assertSame('diamond', $product->stone_type);
        $this->assertSame('12.375', $product->weight_grams);
        $this->assertTrue($product->is_published);
        $this->assertNotNull($product->published_at);
        $this->assertFalse($product->is_featured);
        $this->assertEqualsCanonicalizing($categories->modelKeys(), $product->categories->modelKeys());
        $this->assertSame([$collection->id], $product->collections->modelKeys());
    }

    public function test_store_accepts_every_optional_field_empty(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/products', $this->payload([
            'description_ar' => '', 'description_en' => '', 'karat' => '', 'gold_color' => '',
            'stone_type' => '', 'weight_grams' => '', 'is_published' => '0',
        ]))->assertOk();

        $product = Product::with('translations')->sole();
        $this->assertNull($product->translate('en')->description);
        $this->assertNull($product->brand_id);
        $this->assertNull($product->karat);
        $this->assertNull($product->gold_color);
        $this->assertNull($product->stone_type);
        $this->assertNull($product->weight_grams);
        $this->assertFalse($product->is_published);
        $this->assertNull($product->published_at);
        $this->assertSame(0, $product->categories()->count());
    }

    /* ------------------------------------------------------------ Validation */

    #[DataProvider('locales')]
    public function test_validation_returns_422_json_with_every_error(string $locale): void
    {
        $this->signIn($locale);
        $this->product(['en' => 'Demo Taken', 'ar' => 'مأخوذ'], ['sku' => 'TAKEN-1']);

        $response = $this->postJson("/$locale/admin/products", [
            'name_ar' => '',
            'name_en' => str_repeat('a', 256),
            'description_en' => str_repeat('a', 5001),
            'sku' => 'TAKEN-1',
            'brand_id' => '999',
            'karat' => '22',
            'gold_color' => 'green',
            'stone_type' => 'ruby',
            'weight_grams' => '1.2345',
            'categories' => [999],
            'collections' => 'not-an-array',
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors([
                'name_ar', 'name_en', 'description_en', 'sku', 'brand_id', 'karat', 'gold_color', 'stone_type',
                'weight_grams', 'is_published', 'is_featured', 'categories.0', 'collections',
            ])
            ->assertJsonMissingValidationErrors(['description_ar', 'images', 'remove_images']);

        $nameAr = __('product::product.fields.name', [], $locale).' ('.__('admin.form.language_names.ar', [], $locale).')';
        $this->assertSame(__('validation.required', ['attribute' => $nameAr], $locale), $response->json('errors.name_ar.0'));
        $this->assertSame(1, Product::count());
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidFields(): array
    {
        return [
            'sku with spaces' => [['sku' => 'DEMO 1'], 'sku'],
            'sku in Arabic' => [['sku' => 'كود١'], 'sku'],
            'sku too long' => [['sku' => str_repeat('A', 65)], 'sku'],
            'karat as decimal' => [['karat' => '18.0'], 'karat'],
            'karat with a sign' => [['karat' => '+18'], 'karat'],
            'negative weight' => [['weight_grams' => '-1'], 'weight_grams'],
            'weight too large' => [['weight_grams' => '100000'], 'weight_grams'],
            'weight as text' => [['weight_grams' => 'heavy'], 'weight_grams'],
            'weight in exponent form' => [['weight_grams' => '1e3'], 'weight_grams'],
            'duplicate categories' => [['categories' => ['{c}', '{c}']], 'categories.0'],
            'unknown collection' => [['collections' => [999]], 'collections.0'],
            'publish flag as text' => [['is_published' => 'yes'], 'is_published'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidFields')]
    public function test_invalid_values_are_rejected(array $overrides, string $error): void
    {
        $this->signIn();
        $category = Category::factory()->create();
        array_walk_recursive($overrides, function (&$value) use ($category) {
            $value = $value === '{c}' ? $category->id : $value;
        });

        $this->postJson('/en/admin/products', $this->payload($overrides))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$error]);

        $this->assertSame(0, Product::count());
    }

    public function test_values_at_the_limits_are_accepted(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/products', $this->payload(['sku' => 'Ab.9_x-'.str_repeat('Z', 57), 'weight_grams' => '99999.999', 'karat' => '24']))
            ->assertOk();

        $this->assertSame('99999.999', Product::sole()->weight_grams);
    }

    /* ---------------------------------------------------------------- Update */

    public function test_update_changes_everything_but_the_slug_and_allows_its_own_sku(): void
    {
        $this->signIn();
        $old = Category::factory()->create();
        $new = Category::factory()->create();
        $collection = Collection::factory()->create();
        $product = $this->product(['en' => 'Demo Ring', 'ar' => 'خاتم'], ['sku' => 'KEEP-1']);
        $product->categories()->attach($old);
        $product->collections()->attach($collection);

        $this->putJson("/en/admin/products/{$product->id}", $this->payload([
            'name_en' => 'Demo Fine Ring',
            'sku' => 'KEEP-1',
            'karat' => '18',
            'is_featured' => '1',
            'categories' => [$new->id],
        ]))->assertOk()->assertJsonPath('redirect', 'http://localhost/en/admin/products');

        $product = $product->fresh(['translations', 'categories', 'collections']);
        $this->assertSame('Demo Fine Ring', $product->translate('en')->name);
        $this->assertSame('demo-ring', $product->slug);
        $this->assertSame(18, $product->karat);
        $this->assertTrue($product->is_featured);
        $this->assertSame([$new->id], $product->categories->modelKeys());
        $this->assertSame([], $product->collections->modelKeys());
        $this->assertSame(2, $product->translations()->count());
    }

    public function test_an_edit_of_only_names_relations_or_images_still_marks_the_product_updated(): void
    {
        $this->signIn();
        $category = Category::factory()->create();
        $this->postJson('/en/admin/products', $this->payload())->assertOk();
        $product = Product::query()->sole();

        $edits = [
            'name only' => ['name_en' => 'Demo Renamed Ring'],
            'categories only' => ['name_en' => 'Demo Renamed Ring', 'categories' => [$category->id]],
            'an image only' => [
                'name_en' => 'Demo Renamed Ring',
                'categories' => [$category->id],
                'images' => [UploadedFile::fake()->image('a.jpg', 900, 900)],
            ],
        ];

        foreach ($edits as $edit => $overrides) {
            $before = $product->fresh()->updated_at;
            $this->travel(1)->hours();

            $this->putJson("/en/admin/products/{$product->id}", $this->payload($overrides))->assertOk();

            $this->assertTrue($product->fresh()->updated_at->greaterThan($before), $edit);
        }
    }

    public function test_update_rejects_the_sku_of_another_product_even_in_the_trash(): void
    {
        $this->signIn();
        $this->product(['en' => 'Demo Trashed', 'ar' => 'محذوف'], ['sku' => 'TRASHED-1'])->delete();
        $product = $this->product(['en' => 'Demo Ring', 'ar' => 'خاتم'], ['sku' => 'MINE-1']);

        $this->putJson("/en/admin/products/{$product->id}", $this->payload(['sku' => 'TRASHED-1']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);

        $this->assertSame('MINE-1', $product->fresh()->sku);
    }

    /* --------------------------------------------------------------- Toggles */

    #[DataProvider('locales')]
    public function test_the_published_switch_flips_the_flag_and_changes_visibility(string $locale): void
    {
        $this->signIn($locale);
        $product = Product::factory()->withImages()->create();
        $this->assertFalse(Product::visible()->exists());

        $this->patchJson("/$locale/admin/products/{$product->id}/published")
            ->assertOk()
            ->assertJson([
                'value' => true,
                'label' => __('admin.status.published', [], $locale),
                'message' => __('admin.messages.status_changed', ['status' => __('admin.status.published', [], $locale)], $locale),
            ]);
        $this->assertTrue($product->fresh()->is_published);
        $this->assertNotNull($product->fresh()->published_at);
        $this->assertTrue(Product::visible()->whereKey($product->id)->exists());

        $this->patchJson("/$locale/admin/products/{$product->id}/published")
            ->assertOk()
            ->assertJson(['value' => false, 'label' => __('admin.status.draft', [], $locale)]);
        $this->assertFalse(Product::visible()->exists());
    }

    #[DataProvider('locales')]
    public function test_the_featured_switch_flips_the_flag(string $locale): void
    {
        $this->signIn($locale);
        $product = Product::factory()->create();

        $this->patchJson("/$locale/admin/products/{$product->id}/featured")
            ->assertOk()
            ->assertJson(['value' => true, 'label' => __('product::product.featured.on', [], $locale)]);
        $this->assertTrue($product->fresh()->is_featured);

        $this->patchJson("/$locale/admin/products/{$product->id}/featured")
            ->assertOk()
            ->assertJson(['value' => false, 'label' => __('product::product.featured.off', [], $locale)]);
    }

    /* ------------------------------------------------------ Delete & restore */

    public function test_delete_moves_the_product_to_the_trash_and_keeps_its_files(): void
    {
        $this->signIn();
        $product = Product::factory()->create();
        $image = ProductImage::factory()->for($product)->create();
        Storage::disk('public')->put($image->path, 'x');

        $this->from('/en/admin/products?status=draft')
            ->delete("/en/admin/products/{$product->id}")
            ->assertRedirect('/en/admin/products?status=draft')
            ->assertSessionHas('success', __('product::product.messages.trashed'));

        $this->assertSoftDeleted($product);
        $this->assertModelExists($image);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_restore_brings_the_product_back(): void
    {
        $this->signIn('ar');
        $product = Product::factory()->create();
        $product->delete();

        $this->from('/ar/admin/products?trashed=only')
            ->patch("/ar/admin/products/{$product->id}/restore")
            ->assertRedirect('/ar/admin/products?trashed=only')
            ->assertSessionHas('success', __('admin.messages.restored', [], 'ar'));

        $this->assertNotSoftDeleted($product);
        $this->get("/ar/admin/products/{$product->id}/edit")->assertOk();
    }

    public function test_a_category_with_only_a_trashed_product_still_cannot_be_deleted(): void
    {
        $this->signIn();
        $category = Category::factory()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category);

        $this->delete("/en/admin/products/{$product->id}");
        $this->from('/en/admin/categories')->delete("/en/admin/categories/{$category->id}")->assertSessionHas('error');

        $this->assertModelExists($category);
    }

    /* ---------------------------------------------------------------- Access */

    /** @return array<string, array{string, string}> */
    public static function adminRoutes(): array
    {
        return [
            'index' => ['GET', 'products'],
            'create' => ['GET', 'products/create'],
            'store' => ['POST', 'products'],
            'edit' => ['GET', 'products/{id}/edit'],
            'update' => ['PUT', 'products/{id}'],
            'toggle published' => ['PATCH', 'products/{id}/published'],
            'toggle featured' => ['PATCH', 'products/{id}/featured'],
            'destroy' => ['DELETE', 'products/{id}'],
            'restore' => ['PATCH', 'products/{id}/restore'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_are_redirected_to_the_localized_login(string $method, string $uri): void
    {
        foreach (['ar', 'en'] as $locale) {
            $this->refreshApplicationWithLocale($locale);
            $product = $this->product(['en' => 'Demo Ring', 'ar' => 'خاتم']);

            $this->call($method, "/$locale/admin/".str_replace('{id}', (string) $product->id, $uri), $this->payload())
                ->assertRedirect("http://localhost/$locale/admin/login");

            $this->assertNotSoftDeleted($product);
            $this->assertSame(1, Product::count());
            $this->assertFalse($product->fresh()->is_published);
        }
    }

    #[DataProvider('adminRoutes')]
    public function test_users_of_the_web_guard_cannot_reach_the_admin(string $method, string $uri): void
    {
        $this->refreshApplicationWithLocale('en');
        $product = $this->product(['en' => 'Demo Ring', 'ar' => 'خاتم']);

        $this->actingAs(User::factory()->create())
            ->call($method, '/en/admin/'.str_replace('{id}', (string) $product->id, $uri), $this->payload())
            ->assertRedirect('http://localhost/en/admin/login');

        $this->assertNotSoftDeleted($product);
        $this->assertSame(1, Product::count());
        $this->assertSame('Demo Ring', $product->fresh('translations')->translate('en')->name);
    }
}
