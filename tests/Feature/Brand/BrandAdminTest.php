<?php

namespace Tests\Feature\Brand;

use App\Exceptions\ModelInUseException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\Admin;
use Modules\Brand\Models\Brand;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Brands admin: built like Categories, text only (no logo until the owner
 * confirms permission to use brand logos).
 */
class BrandAdminTest extends TestCase
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
            'name_ar' => 'علامة تجريبية',
            'name_en' => 'Demo Maison',
            'is_active' => '1',
            'sort_order' => '3',
        ], $overrides);
    }

    /**
     * @param  array<string, string>  $names
     */
    private function brand(array $names, array $attributes = []): Brand
    {
        return Brand::factory()->create([
            ...$attributes,
            'en' => ['name' => $names['en']],
            'ar' => ['name' => $names['ar']],
        ]);
    }

    /* ----------------------------------------------------------------- Index */

    #[DataProvider('locales')]
    public function test_the_index_lists_brands_with_status_text_and_both_names(string $locale): void
    {
        $this->signIn($locale);
        $active = $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة تجريبية']);
        $inactive = $this->brand(['en' => 'Demo Atelier', 'ar' => 'مشغل تجريبي'], ['is_active' => false]);
        Product::factory()->count(2)->create(['brand_id' => $active->id]);

        $this->get("/$locale/admin/brands")
            ->assertOk()
            ->assertSee('<html lang="'.$locale.'"', false)
            ->assertSee(__('brand::brand.title', [], $locale))
            ->assertSee('Demo Maison')
            ->assertSee('علامة تجريبية')
            ->assertSee('Demo Atelier')
            ->assertSee(__('admin.status.active', [], $locale))
            ->assertSee(__('admin.status.inactive', [], $locale))
            ->assertSee('data-toggle-url="'.route('admin.brands.toggle-active', $inactive).'"', false)
            ->assertSee('data-url="'.route('admin.brands.destroy', $active).'"', false)
            ->assertSee('href="'.route('admin.brands.edit', $active).'"', false)
            ->assertSee('<td class="d-none d-md-table-cell">2</td>', false)
            // Text only: no thumbnail column.
            ->assertDontSee('yq-table__thumb', false);
    }

    public function test_the_products_count_leaves_out_trashed_products(): void
    {
        $this->signIn();
        $brand = $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);
        Product::factory()->count(3)->create(['brand_id' => $brand->id])->first()->delete();

        $this->get('/en/admin/brands')->assertOk()->assertSee('<td class="d-none d-md-table-cell">2</td>', false);
    }

    public function test_the_index_searches_names_in_either_language(): void
    {
        $this->signIn();
        $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة تجريبية']);
        $this->brand(['en' => 'Demo Atelier', 'ar' => 'مشغل تجريبي']);

        $this->get('/en/admin/brands?q=mais')->assertOk()->assertSee('Demo Maison')->assertDontSee('Demo Atelier');
        $this->get('/en/admin/brands?q='.urlencode('مشغل'))->assertOk()->assertSee('Demo Atelier')->assertDontSee('Demo Maison');
    }

    public function test_the_search_treats_like_wildcards_literally(): void
    {
        $this->signIn();
        $this->brand(['en' => 'Demo 100% Gold', 'ar' => 'ذهب تجريبي']);
        $this->brand(['en' => 'Demo Silverware', 'ar' => 'فضيات تجريبية']);

        $this->get('/en/admin/brands?q=%25')->assertOk()->assertSee('Demo 100% Gold')->assertDontSee('Demo Silverware');
        $this->get('/en/admin/brands?q=_')->assertOk()->assertSee(__('admin.listing.empty_filtered'));
    }

    public function test_the_index_filters_by_status(): void
    {
        $this->signIn();
        $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);
        $this->brand(['en' => 'Demo Atelier', 'ar' => 'مشغل'], ['is_active' => false]);

        $this->get('/en/admin/brands?status=inactive')->assertOk()->assertSee('Demo Atelier')->assertDontSee('Demo Maison');
        $this->get('/en/admin/brands?status=active')->assertOk()->assertSee('Demo Maison')->assertDontSee('Demo Atelier');
        // An unknown value is ignored rather than failing.
        $this->get('/en/admin/brands?status=deleted')->assertOk()->assertSee('Demo Maison')->assertSee('Demo Atelier');
    }

    public function test_the_index_sorts_by_allowlisted_columns_only(): void
    {
        $this->signIn();
        $this->brand(['en' => 'Demo Bravo', 'ar' => 'ب'], ['sort_order' => 1]);
        $this->brand(['en' => 'Demo Alpha', 'ar' => 'أ'], ['sort_order' => 2]);
        $charlie = $this->brand(['en' => 'Demo Charlie', 'ar' => 'ج'], ['sort_order' => 3]);
        Product::factory()->create(['brand_id' => $charlie->id]);

        // Default: sort order.
        $this->get('/en/admin/brands')->assertSeeInOrder(['Demo Bravo', 'Demo Alpha', 'Demo Charlie']);
        $this->get('/en/admin/brands?sort=name')->assertSeeInOrder(['Demo Alpha', 'Demo Bravo', 'Demo Charlie']);
        $this->get('/en/admin/brands?sort=name&dir=desc')->assertSeeInOrder(['Demo Charlie', 'Demo Bravo', 'Demo Alpha']);
        $this->get('/en/admin/brands?sort=products_count&dir=desc')->assertSeeInOrder(['Demo Charlie', 'Demo Bravo']);
        // Not allowlisted: falls back to the default order instead of reaching the query.
        $this->get('/en/admin/brands?sort=slug&dir=desc')->assertOk()->assertSeeInOrder(['Demo Bravo', 'Demo Alpha', 'Demo Charlie']);
        $this->get('/en/admin/brands?sort=id);drop table brands;--')->assertOk();
    }

    public function test_sorting_by_name_follows_the_admin_locale(): void
    {
        $this->signIn('ar');
        $this->brand(['en' => 'Demo Alpha', 'ar' => 'ياقوت تجريبي']);
        $this->brand(['en' => 'Demo Zulu', 'ar' => 'أساور تجريبية']);

        $this->get('/ar/admin/brands?sort=name')->assertOk()->assertSeeInOrder(['أساور تجريبية', 'ياقوت تجريبي']);
    }

    public function test_the_index_paginates_on_the_server_and_keeps_the_query_string(): void
    {
        $this->signIn();
        config(['catalog.admin.per_page' => 2]);
        foreach (range(1, 5) as $i) {
            $this->brand(['en' => "Demo Item $i", 'ar' => "عنصر $i"], ['sort_order' => $i]);
        }

        $this->get('/en/admin/brands?status=active')
            ->assertOk()
            ->assertSee('Demo Item 1')
            ->assertDontSee('Demo Item 3')
            ->assertSee('status=active&amp;page=2', false);

        $this->get('/en/admin/brands?page=3')->assertOk()->assertSee('Demo Item 5')->assertDontSee('Demo Item 1');
    }

    public function test_a_page_past_the_end_redirects_to_the_last_page(): void
    {
        $this->signIn();
        config(['catalog.admin.per_page' => 2]);
        foreach (range(1, 3) as $i) {
            $this->brand(['en' => "Demo Item $i", 'ar' => "عنصر $i"]);
        }

        $this->get('/en/admin/brands?status=active&page=9')
            ->assertRedirect('http://localhost/en/admin/brands?status=active&page=2');
    }

    #[DataProvider('locales')]
    public function test_the_index_has_empty_states(string $locale): void
    {
        $this->signIn($locale);

        $this->get("/$locale/admin/brands")
            ->assertOk()
            ->assertSee(__('brand::brand.empty', [], $locale))
            ->assertSee('href="'.route('admin.brands.create').'"', false);

        $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);

        $this->get("/$locale/admin/brands?q=nothing-matches")
            ->assertOk()
            ->assertSee(__('admin.listing.empty_filtered', [], $locale))
            ->assertDontSee(__('brand::brand.empty', [], $locale));
    }

    /* ------------------------------------------------------- Create and edit */

    #[DataProvider('locales')]
    public function test_the_create_page_has_the_sectioned_form_with_locale_tabs(string $locale): void
    {
        $this->signIn($locale);

        $response = $this->get("/$locale/admin/brands/create");

        // New brands start active.
        $this->assertMatchesRegularExpression('/name="is_active"\s+value="1" checked/', $response->getContent());

        $response->assertOk()
            ->assertSee(__('brand::brand.create', [], $locale))
            ->assertSee('class="ajax-form"', false)
            ->assertSee('action="'.route('admin.brands.store').'"', false)
            ->assertSeeInOrder(['data-bs-target="#translations-ar"', 'data-bs-target="#translations-en"'], false)
            ->assertSee('name="name_ar"', false)
            ->assertSee('name="name_en"', false)
            ->assertSee('name="sort_order"', false)
            // Text only: no description and no logo.
            ->assertDontSee('name="description_en"', false)
            ->assertDontSee('type="file"', false)
            ->assertDontSee('name="_method" value="PUT"', false);
    }

    #[DataProvider('locales')]
    public function test_the_edit_page_is_filled_with_both_translations(string $locale): void
    {
        $this->signIn($locale);
        $brand = $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة تجريبية'], ['is_active' => false, 'sort_order' => 7]);

        $response = $this->get("/$locale/admin/brands/{$brand->id}/edit");

        $this->assertMatchesRegularExpression('/name="is_active"\s+value="1"\s+aria-describedby/', $response->getContent());

        $response->assertOk()
            ->assertSee(__('brand::brand.edit', [], $locale))
            ->assertSee('action="'.route('admin.brands.update', $brand).'"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('value="Demo Maison"', false)
            ->assertSee('value="علامة تجريبية"', false)
            ->assertSee('value="7"', false);
    }

    public function test_an_unknown_brand_returns_404(): void
    {
        $this->signIn();

        $this->get('/en/admin/brands/999/edit')->assertNotFound();
        $this->putJson('/en/admin/brands/999', $this->payload())->assertNotFound();
        $this->patchJson('/en/admin/brands/999/active')->assertNotFound();
        $this->delete('/en/admin/brands/999')->assertNotFound();
    }

    /* ----------------------------------------------------------------- Store */

    #[DataProvider('locales')]
    public function test_store_creates_the_brand_with_both_translations(string $locale): void
    {
        $this->signIn($locale);

        $this->postJson("/$locale/admin/brands", $this->payload())
            ->assertOk()
            ->assertExactJson([
                'message' => __('admin.messages.saved', [], $locale),
                'redirect' => "http://localhost/$locale/admin/brands",
            ]);

        $brand = Brand::with('translations')->sole();
        $this->assertSame('Demo Maison', $brand->translate('en')->name);
        $this->assertSame('علامة تجريبية', $brand->translate('ar')->name);
        $this->assertSame('demo-maison', $brand->slug);
        $this->assertTrue($brand->is_active);
        $this->assertSame(3, $brand->sort_order);
    }

    public function test_store_saves_an_inactive_brand(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/brands', $this->payload(['is_active' => '0', 'sort_order' => '0']))->assertOk();

        $brand = Brand::sole();
        $this->assertFalse($brand->is_active);
        $this->assertSame(0, $brand->sort_order);
    }

    public function test_an_uploaded_file_is_ignored(): void
    {
        $this->signIn();

        // Brands have no logo field: a stray upload is neither validated nor stored.
        $this->postJson('/en/admin/brands', $this->payload(['image' => UploadedFile::fake()->image('logo.png', 400, 400)]))
            ->assertOk();

        $this->assertSame(1, Brand::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /* ------------------------------------------------------------ Validation */

    #[DataProvider('locales')]
    public function test_validation_returns_422_json_with_every_error(string $locale): void
    {
        $this->signIn($locale);

        $response = $this->postJson("/$locale/admin/brands", [
            'name_ar' => '',
            'name_en' => str_repeat('a', 256),
            'is_active' => 'maybe',
            'sort_order' => '65536',
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors(['name_ar', 'name_en', 'is_active', 'sort_order']);

        // Messages are in the admin's language and name the field's language tab.
        $nameAr = __('brand::brand.fields.name', [], $locale).' ('.__('admin.form.language_names.ar', [], $locale).')';
        $this->assertSame(
            __('validation.required', ['attribute' => $nameAr], $locale),
            $response->json('errors.name_ar.0'),
        );

        $this->assertSame(0, Brand::count());
    }

    public function test_both_names_are_required(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/brands', ['is_active' => '1', 'sort_order' => '0'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name_ar', 'name_en'])
            ->assertJsonMissingValidationErrors(['is_active', 'sort_order']);

        $this->assertSame(0, Brand::count());
    }

    /* ---------------------------------------------------------------- Update */

    public function test_update_changes_fields_and_translations_but_never_the_slug(): void
    {
        $this->signIn();
        $brand = $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);

        $this->putJson("/en/admin/brands/{$brand->id}", $this->payload([
            'name_en' => 'Demo Maison Fine',
            'name_ar' => 'علامة فاخرة',
            'is_active' => '0',
            'sort_order' => '9',
        ]))->assertOk()->assertJsonPath('redirect', 'http://localhost/en/admin/brands');

        $brand = $brand->fresh('translations');
        $this->assertSame('Demo Maison Fine', $brand->translate('en')->name);
        $this->assertSame('علامة فاخرة', $brand->translate('ar')->name);
        $this->assertSame('demo-maison', $brand->slug);
        $this->assertFalse($brand->is_active);
        $this->assertSame(9, $brand->sort_order);
        $this->assertSame(2, $brand->translations()->count());
    }

    public function test_update_validation_returns_422_and_changes_nothing(): void
    {
        $this->signIn();
        $brand = $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);

        $this->putJson("/en/admin/brands/{$brand->id}", $this->payload(['name_en' => '   ']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name_en']);

        $this->assertSame('Demo Maison', $brand->fresh('translations')->translate('en')->name);
    }

    /* ---------------------------------------------------------------- Toggle */

    #[DataProvider('locales')]
    public function test_the_active_switch_flips_the_flag_and_returns_the_text_status(string $locale): void
    {
        $this->signIn($locale);
        $brand = $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);

        $this->patchJson("/$locale/admin/brands/{$brand->id}/active")
            ->assertOk()
            ->assertJson([
                'value' => false,
                'label' => __('admin.status.inactive', [], $locale),
                'message' => __('admin.messages.status_changed', ['status' => __('admin.status.inactive', [], $locale)], $locale),
            ]);
        $this->assertFalse($brand->fresh()->is_active);

        $this->patchJson("/$locale/admin/brands/{$brand->id}/active")
            ->assertOk()
            ->assertJson(['value' => true, 'label' => __('admin.status.active', [], $locale)]);
        $this->assertTrue($brand->fresh()->is_active);
    }

    /* ---------------------------------------------------------------- Delete */

    public function test_delete_removes_the_brand_and_its_translations(): void
    {
        $this->signIn();
        $brand = $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);

        $this->from('/en/admin/brands?status=active')
            ->delete("/en/admin/brands/{$brand->id}")
            ->assertRedirect('/en/admin/brands?status=active')
            ->assertSessionHas('success', __('admin.messages.deleted'));

        $this->assertModelMissing($brand);
        $this->assertSame(0, $brand->translations()->count());
    }

    public function test_the_success_flash_is_rendered_for_the_toast(): void
    {
        $this->signIn('ar');
        $brand = $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);

        $this->from('/ar/admin/brands')->followingRedirects()
            ->delete("/ar/admin/brands/{$brand->id}")
            ->assertOk()
            ->assertSee('data-flash-message="'.__('admin.messages.deleted', [], 'ar').'" data-flash-type="success"', false);
    }

    public function test_the_delete_modal_names_the_item(): void
    {
        $this->signIn();
        $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);

        $this->get('/en/admin/brands')
            ->assertSee('data-name="Demo Maison"', false)
            ->assertSee('id="delete-modal-body"', false)
            ->assertSee('"deleteConfirm":', false);
    }

    /** @return array<string, array{string, int, int, string}> */
    public static function refusals(): array
    {
        // locale, products of the brand, of which trashed, expected message
        return [
            'en, one product' => ['en', 1, 0, 'This item is linked to 1 product, so it cannot be deleted. Deactivate it instead.'],
            'en, some trashed' => ['en', 5, 2, 'This item is linked to 5 products (2 of them are in the trash and still count), so it cannot be deleted. Deactivate it instead.'],
            'en, only trashed' => ['en', 1, 1, 'This item is linked to 1 product (1 of them is in the trash and still counts), so it cannot be deleted. Deactivate it instead.'],
            'ar, one product' => ['ar', 1, 0, 'لا يمكن حذف هذا العنصر لأنه مرتبط بمنتجات (منتج واحد). يمكنك إلغاء تفعيله بدلًا من ذلك.'],
            'ar, one trashed' => ['ar', 3, 1, 'لا يمكن حذف هذا العنصر لأنه مرتبط بمنتجات (3 منتجات، منها منتج واحد في سلة المحذوفات). يمكنك إلغاء تفعيله بدلًا من ذلك.'],
            'ar, only trashed' => ['ar', 2, 2, 'لا يمكن حذف هذا العنصر لأنه مرتبط بمنتجات (منتجان، منها منتجان في سلة المحذوفات). يمكنك إلغاء تفعيله بدلًا من ذلك.'],
        ];
    }

    #[DataProvider('refusals')]
    public function test_a_refused_delete_explains_why_with_product_and_trash_counts(string $locale, int $products, int $trashed, string $message): void
    {
        $this->signIn($locale);
        $brand = $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);
        Product::factory()->count($products)->create(['brand_id' => $brand->id])->take($trashed)->each->delete();

        $this->from("/$locale/admin/brands")
            ->delete("/$locale/admin/brands/{$brand->id}")
            ->assertRedirect("/$locale/admin/brands")
            ->assertSessionHas('error', $message)
            ->assertSessionMissing('success');

        $this->assertModelExists($brand);

        // The layout hands the message to admin.js as a danger toast.
        $this->get("/$locale/admin/brands")
            ->assertSee('data-flash-message="'.e($message).'" data-flash-type="danger"', false);
    }

    public function test_a_refused_delete_requested_as_json_returns_409(): void
    {
        $this->signIn();
        $brand = $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);
        Product::factory()->create(['brand_id' => $brand->id]);

        $this->deleteJson("/en/admin/brands/{$brand->id}")
            ->assertStatus(409)
            ->assertExactJson(['message' => ModelInUseException::describe(1, 0)]);

        $this->assertModelExists($brand);
    }

    public function test_a_refused_delete_is_not_reported_as_an_error(): void
    {
        $this->signIn();
        Exceptions::fake();
        $brand = $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);
        Product::factory()->create(['brand_id' => $brand->id]);

        $this->from('/en/admin/brands')->delete("/en/admin/brands/{$brand->id}")->assertRedirect();

        Exceptions::assertNothingReported();
    }

    /* ---------------------------------------------------------------- Access */

    /** @return array<string, array{string, string}> */
    public static function adminRoutes(): array
    {
        return [
            'index' => ['GET', 'brands'],
            'create' => ['GET', 'brands/create'],
            'store' => ['POST', 'brands'],
            'edit' => ['GET', 'brands/{id}/edit'],
            'update' => ['PUT', 'brands/{id}'],
            'toggle' => ['PATCH', 'brands/{id}/active'],
            'destroy' => ['DELETE', 'brands/{id}'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_are_redirected_to_the_localized_login(string $method, string $uri): void
    {
        foreach (['ar', 'en'] as $locale) {
            $this->refreshApplicationWithLocale($locale);
            $brand = $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);

            $this->call($method, "/$locale/admin/".str_replace('{id}', (string) $brand->id, $uri), $this->payload())
                ->assertRedirect("http://localhost/$locale/admin/login");

            $this->assertModelExists($brand);
            $this->assertSame(1, Brand::count());
            $this->assertTrue($brand->fresh()->is_active);
        }
    }

    #[DataProvider('adminRoutes')]
    public function test_users_of_the_web_guard_cannot_reach_the_admin(string $method, string $uri): void
    {
        $this->refreshApplicationWithLocale('en');
        $brand = $this->brand(['en' => 'Demo Maison', 'ar' => 'علامة']);

        $this->actingAs(User::factory()->create())
            ->call($method, '/en/admin/'.str_replace('{id}', (string) $brand->id, $uri), $this->payload())
            ->assertRedirect('http://localhost/en/admin/login');

        $this->assertSame(1, Brand::count());
        $this->assertTrue($brand->fresh()->is_active);
        $this->assertSame('Demo Maison', $brand->fresh('translations')->translate('en')->name);
    }

    public function test_guests_asking_for_json_get_401(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->postJson('/en/admin/brands', $this->payload())->assertUnauthorized();
        $this->assertSame(0, Brand::count());
    }
}
