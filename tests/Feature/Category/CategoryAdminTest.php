<?php

namespace Tests\Feature\Category;

use App\Exceptions\ModelInUseException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\Admin;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Categories admin: the first resource built on the shared admin building blocks
 * (AdminListing, components/admin, ImageUploader, the delete modal and toggles).
 */
class CategoryAdminTest extends TestCase
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
            'name_ar' => 'خواتم تجريبية',
            'name_en' => 'Demo Rings',
            'description_ar' => 'وصف تجريبي.',
            'description_en' => 'A demo description.',
            'is_active' => '1',
            'sort_order' => '3',
        ], $overrides);
    }

    /**
     * @param  array<string, string>  $names
     */
    private function category(array $names, array $attributes = []): Category
    {
        return Category::factory()->create([
            ...$attributes,
            'en' => ['name' => $names['en']],
            'ar' => ['name' => $names['ar']],
        ]);
    }

    /* ----------------------------------------------------------------- Index */

    #[DataProvider('locales')]
    public function test_the_index_lists_categories_with_status_text_and_both_names(string $locale): void
    {
        $this->signIn($locale);
        $active = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم تجريبية']);
        $inactive = $this->category(['en' => 'Demo Anklets', 'ar' => 'خلاخيل تجريبية'], ['is_active' => false]);
        $active->products()->attach(Product::factory()->count(2)->create());

        $this->get("/$locale/admin/categories")
            ->assertOk()
            ->assertSee('<html lang="'.$locale.'"', false)
            ->assertSee(__('category::category.title', [], $locale))
            ->assertSee('Demo Rings')
            ->assertSee('خواتم تجريبية')
            ->assertSee('Demo Anklets')
            ->assertSee(__('admin.status.active', [], $locale))
            ->assertSee(__('admin.status.inactive', [], $locale))
            ->assertSee('data-toggle-url="'.route('admin.categories.toggle-active', $inactive).'"', false)
            ->assertSee('data-url="'.route('admin.categories.destroy', $active).'"', false)
            ->assertSee('href="'.route('admin.categories.edit', $active).'"', false)
            ->assertSee('<td class="d-none d-md-table-cell">2</td>', false);
    }

    public function test_the_index_searches_names_in_either_language(): void
    {
        $this->signIn();
        $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم تجريبية']);
        $this->category(['en' => 'Demo Bracelets', 'ar' => 'أساور تجريبية']);

        $this->get('/en/admin/categories?q=ring')->assertOk()->assertSee('Demo Rings')->assertDontSee('Demo Bracelets');
        $this->get('/en/admin/categories?q='.urlencode('أساور'))->assertOk()->assertSee('Demo Bracelets')->assertDontSee('Demo Rings');
    }

    public function test_the_search_treats_like_wildcards_literally(): void
    {
        $this->signIn();
        $this->category(['en' => 'Demo 100% Gold', 'ar' => 'ذهب تجريبي']);
        $this->category(['en' => 'Demo Silverware', 'ar' => 'فضيات تجريبية']);

        $this->get('/en/admin/categories?q=%25')->assertOk()->assertSee('Demo 100% Gold')->assertDontSee('Demo Silverware');
        $this->get('/en/admin/categories?q=_')->assertOk()->assertSee(__('admin.listing.empty_filtered'));
    }

    public function test_the_index_filters_by_status(): void
    {
        $this->signIn();
        $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم']);
        $this->category(['en' => 'Demo Anklets', 'ar' => 'خلاخيل'], ['is_active' => false]);

        $this->get('/en/admin/categories?status=inactive')->assertOk()->assertSee('Demo Anklets')->assertDontSee('Demo Rings');
        $this->get('/en/admin/categories?status=active')->assertOk()->assertSee('Demo Rings')->assertDontSee('Demo Anklets');
        // An unknown value is ignored rather than failing.
        $this->get('/en/admin/categories?status=deleted')->assertOk()->assertSee('Demo Rings')->assertSee('Demo Anklets');
    }

    public function test_the_index_sorts_by_allowlisted_columns_only(): void
    {
        $this->signIn();
        $this->category(['en' => 'Demo Bravo', 'ar' => 'ب'], ['sort_order' => 1]);
        $this->category(['en' => 'Demo Alpha', 'ar' => 'أ'], ['sort_order' => 2]);
        $this->category(['en' => 'Demo Charlie', 'ar' => 'ج'], ['sort_order' => 3])
            ->products()->attach(Product::factory()->create());

        // Default: sort order.
        $this->get('/en/admin/categories')->assertSeeInOrder(['Demo Bravo', 'Demo Alpha', 'Demo Charlie']);
        $this->get('/en/admin/categories?sort=name')->assertSeeInOrder(['Demo Alpha', 'Demo Bravo', 'Demo Charlie']);
        $this->get('/en/admin/categories?sort=name&dir=desc')->assertSeeInOrder(['Demo Charlie', 'Demo Bravo', 'Demo Alpha']);
        $this->get('/en/admin/categories?sort=products_count&dir=desc')->assertSeeInOrder(['Demo Charlie', 'Demo Bravo']);
        // Not allowlisted: falls back to the default order instead of reaching the query.
        $this->get('/en/admin/categories?sort=slug&dir=desc')->assertOk()->assertSeeInOrder(['Demo Bravo', 'Demo Alpha', 'Demo Charlie']);
        $this->get('/en/admin/categories?sort=id);drop table categories;--')->assertOk();
    }

    public function test_sorting_by_name_follows_the_admin_locale(): void
    {
        $this->signIn('ar');
        $this->category(['en' => 'Demo Alpha', 'ar' => 'ياقوت تجريبي']);
        $this->category(['en' => 'Demo Zulu', 'ar' => 'أساور تجريبية']);

        $this->get('/ar/admin/categories?sort=name')->assertOk()->assertSeeInOrder(['أساور تجريبية', 'ياقوت تجريبي']);
    }

    public function test_the_index_paginates_on_the_server_and_keeps_the_query_string(): void
    {
        $this->signIn();
        config(['catalog.admin.per_page' => 2]);
        foreach (range(1, 5) as $i) {
            $this->category(['en' => "Demo Item $i", 'ar' => "عنصر $i"], ['sort_order' => $i]);
        }

        $this->get('/en/admin/categories?status=active')
            ->assertOk()
            ->assertSee('Demo Item 1')
            ->assertDontSee('Demo Item 3')
            ->assertSee('status=active&amp;page=2', false);

        $this->get('/en/admin/categories?page=3')->assertOk()->assertSee('Demo Item 5')->assertDontSee('Demo Item 1');
    }

    public function test_a_page_past_the_end_redirects_to_the_last_page(): void
    {
        $this->signIn();
        config(['catalog.admin.per_page' => 2]);
        foreach (range(1, 3) as $i) {
            $this->category(['en' => "Demo Item $i", 'ar' => "عنصر $i"]);
        }

        $this->get('/en/admin/categories?status=active&page=9')
            ->assertRedirect('http://localhost/en/admin/categories?status=active&page=2');
    }

    #[DataProvider('locales')]
    public function test_the_index_has_empty_states(string $locale): void
    {
        $this->signIn($locale);

        $this->get("/$locale/admin/categories")
            ->assertOk()
            ->assertSee(__('category::category.empty', [], $locale))
            ->assertSee('href="'.route('admin.categories.create').'"', false);

        $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم']);

        $this->get("/$locale/admin/categories?q=nothing-matches")
            ->assertOk()
            ->assertSee(__('admin.listing.empty_filtered', [], $locale))
            ->assertDontSee(__('category::category.empty', [], $locale));
    }

    /* ------------------------------------------------------- Create and edit */

    #[DataProvider('locales')]
    public function test_the_create_page_has_the_sectioned_form_with_locale_tabs(string $locale): void
    {
        $this->signIn($locale);

        $response = $this->get("/$locale/admin/categories/create");

        // New categories start active.
        $this->assertMatchesRegularExpression('/name="is_active"\s+value="1" checked/', $response->getContent());

        $response->assertOk()
            ->assertSee(__('category::category.create', [], $locale))
            ->assertSee('class="ajax-form"', false)
            ->assertSee('action="'.route('admin.categories.store').'"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSeeInOrder(['data-bs-target="#translations-ar"', 'data-bs-target="#translations-en"'], false)
            ->assertSee('name="name_ar"', false)
            ->assertSee('name="name_en"', false)
            ->assertSee('name="description_en"', false)
            ->assertSee('name="image"', false)
            ->assertDontSee('name="remove_image"', false)
            ->assertSee('name="sort_order"', false)
            ->assertDontSee('name="_method" value="PUT"', false);
    }

    #[DataProvider('locales')]
    public function test_the_edit_page_is_filled_with_both_translations_and_the_image(string $locale): void
    {
        $this->signIn($locale);
        Storage::disk('public')->put('categories/demo.webp', 'x');
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم تجريبية'], ['image_path' => 'categories/demo.webp', 'is_active' => false]);

        $response = $this->get("/$locale/admin/categories/{$category->id}/edit");

        $this->assertMatchesRegularExpression('/name="is_active"\s+value="1"\s+aria-describedby/', $response->getContent());

        $response->assertOk()
            ->assertSee(__('category::category.edit', [], $locale))
            ->assertSee('action="'.route('admin.categories.update', $category).'"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('value="Demo Rings"', false)
            ->assertSee('value="خواتم تجريبية"', false)
            ->assertSee(Storage::disk('public')->url('categories/demo.webp'))
            ->assertSee('name="remove_image"', false);
    }

    public function test_the_public_disk_stays_faked_after_a_locale_reboot(): void
    {
        // refreshApplicationWithLocale() boots a new application; the fake must survive it,
        // or uploads in these tests would land in the real storage/app/public.
        $this->signIn('ar');

        $this->assertStringContainsString(
            'framework/testing/disks/public',
            str_replace('\\', '/', Storage::disk('public')->path('')),
        );
    }

    public function test_an_unknown_category_returns_404(): void
    {
        $this->signIn();

        $this->get('/en/admin/categories/999/edit')->assertNotFound();
        $this->putJson('/en/admin/categories/999', $this->payload())->assertNotFound();
        $this->delete('/en/admin/categories/999')->assertNotFound();
    }

    /* ----------------------------------------------------------------- Store */

    #[DataProvider('locales')]
    public function test_store_creates_the_category_with_both_translations(string $locale): void
    {
        $this->signIn($locale);

        $this->postJson("/$locale/admin/categories", $this->payload())
            ->assertOk()
            ->assertExactJson([
                'message' => __('admin.messages.saved', [], $locale),
                'redirect' => "http://localhost/$locale/admin/categories",
            ]);

        $category = Category::with('translations')->sole();
        $this->assertSame('Demo Rings', $category->translate('en')->name);
        $this->assertSame('خواتم تجريبية', $category->translate('ar')->name);
        $this->assertSame('A demo description.', $category->translate('en')->description);
        $this->assertSame('demo-rings', $category->slug);
        $this->assertTrue($category->is_active);
        $this->assertSame(3, $category->sort_order);
        $this->assertNull($category->image_path);
    }

    public function test_store_accepts_empty_optional_fields(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/categories', $this->payload(['description_ar' => '', 'description_en' => '', 'is_active' => '0']))
            ->assertOk();

        $category = Category::with('translations')->sole();
        $this->assertNull($category->translate('en')->description);
        $this->assertFalse($category->is_active);
    }

    public function test_store_with_an_image_saves_a_resized_webp(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/categories', $this->payload(['image' => UploadedFile::fake()->image('photo.jpg', 2400, 1800)]))
            ->assertOk();

        $path = Category::sole()->image_path;
        $this->assertMatchesRegularExpression('#^categories/[0-9a-f-]{36}\.webp$#', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame([1600, 1200], array_slice(getimagesizefromstring(Storage::disk('public')->get($path)), 0, 2));
    }

    /* ------------------------------------------------------------ Validation */

    #[DataProvider('locales')]
    public function test_validation_returns_422_json_with_every_error(string $locale): void
    {
        $this->signIn($locale);

        $response = $this->postJson("/$locale/admin/categories", [
            'name_ar' => '',
            'name_en' => str_repeat('a', 256),
            'description_en' => ['not', 'a', 'string'],
            'sort_order' => '-1',
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors(['name_ar', 'name_en', 'description_en', 'is_active', 'sort_order'])
            ->assertJsonMissingValidationErrors(['description_ar', 'image', 'remove_image']);

        // Messages are in the admin's language and name the field's language tab.
        $nameAr = __('category::category.fields.name', [], $locale).' ('.__('admin.form.language_names.ar', [], $locale).')';
        $this->assertSame(
            __('validation.required', ['attribute' => $nameAr], $locale),
            $response->json('errors.name_ar.0'),
        );

        $this->assertSame(0, Category::count());
    }

    public function test_every_failing_rule_of_a_field_is_reported(): void
    {
        $this->signIn();

        // Not an image and too large: both messages come back, not just the first.
        $this->postJson('/en/admin/categories', $this->payload([
            'image' => UploadedFile::fake()->create('notes.pdf', 6000, 'application/pdf'),
        ]))->assertUnprocessable()->assertJsonCount(4, 'errors.image');
    }

    /** @return array<string, array{\Closure(): UploadedFile}> */
    public static function invalidImages(): array
    {
        return [
            'gif' => [fn () => UploadedFile::fake()->image('anim.gif', 400, 400)],
            'bmp' => [fn () => UploadedFile::fake()->image('old.bmp', 400, 400)],
            'svg' => [fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"></svg>')],
            'pdf' => [fn () => UploadedFile::fake()->create('brochure.pdf', 100, 'application/pdf')],
            'text renamed to jpg' => [fn () => UploadedFile::fake()->createWithContent('fake.jpg', 'this is not an image')],
            'php renamed to png' => [fn () => UploadedFile::fake()->createWithContent('shell.png', '<?php echo 1;')],
            'over 5 MB' => [fn () => UploadedFile::fake()->image('huge.jpg', 400, 400)->size(5121)],
            'too small' => [fn () => UploadedFile::fake()->image('tiny.png', 199, 400)],
            'too wide' => [fn () => UploadedFile::fake()->image('panorama.png', 6001, 300)],
            'too tall' => [fn () => UploadedFile::fake()->image('banner.png', 300, 6001)],
        ];
    }

    #[DataProvider('invalidImages')]
    public function test_invalid_images_are_rejected_and_nothing_is_stored(\Closure $file): void
    {
        $this->signIn();

        $this->postJson('/en/admin/categories', $this->payload(['image' => $file()]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);

        $this->assertSame(0, Category::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_images_at_the_limits_are_accepted(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/categories', $this->payload([
            'name_en' => 'Demo Limit A',
            'image' => UploadedFile::fake()->image('min.png', 200, 200)->size(5120),
        ]))->assertOk();

        $this->postJson('/en/admin/categories', $this->payload([
            'name_en' => 'Demo Limit B',
            'image' => UploadedFile::fake()->image('max.webp', 6000, 200),
        ]))->assertOk();

        $this->assertCount(2, Storage::disk('public')->allFiles('categories'));
    }

    /* ---------------------------------------------------------------- Update */

    public function test_update_changes_fields_and_translations_but_never_the_slug(): void
    {
        $this->signIn();
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم']);

        $this->putJson("/en/admin/categories/{$category->id}", $this->payload([
            'name_en' => 'Demo Fine Rings',
            'name_ar' => 'خواتم فاخرة',
            'is_active' => '0',
            'sort_order' => '9',
        ]))->assertOk()->assertJsonPath('redirect', 'http://localhost/en/admin/categories');

        $category = $category->fresh('translations');
        $this->assertSame('Demo Fine Rings', $category->translate('en')->name);
        $this->assertSame('خواتم فاخرة', $category->translate('ar')->name);
        $this->assertSame('demo-rings', $category->slug);
        $this->assertFalse($category->is_active);
        $this->assertSame(9, $category->sort_order);
        $this->assertSame(2, $category->translations()->count());
    }

    public function test_update_validation_returns_422_and_changes_nothing(): void
    {
        $this->signIn();
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم']);

        $this->putJson("/en/admin/categories/{$category->id}", $this->payload(['name_en' => '   ']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name_en']);

        $this->assertSame('Demo Rings', $category->fresh('translations')->translate('en')->name);
    }

    public function test_replacing_the_image_deletes_the_old_file(): void
    {
        $this->signIn();
        Storage::disk('public')->put('categories/old.webp', 'x');
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم'], ['image_path' => 'categories/old.webp']);

        $this->putJson("/en/admin/categories/{$category->id}", $this->payload([
            'image' => UploadedFile::fake()->image('new.png', 500, 500),
        ]))->assertOk();

        $new = $category->fresh()->image_path;
        $this->assertNotSame('categories/old.webp', $new);
        Storage::disk('public')->assertExists($new);
        Storage::disk('public')->assertMissing('categories/old.webp');
    }

    public function test_removing_the_image_deletes_the_file(): void
    {
        $this->signIn();
        Storage::disk('public')->put('categories/old.webp', 'x');
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم'], ['image_path' => 'categories/old.webp']);

        $this->putJson("/en/admin/categories/{$category->id}", $this->payload(['remove_image' => '1']))->assertOk();

        $this->assertNull($category->fresh()->image_path);
        Storage::disk('public')->assertMissing('categories/old.webp');
    }

    public function test_saving_without_a_new_image_keeps_the_current_one(): void
    {
        $this->signIn();
        Storage::disk('public')->put('categories/keep.webp', 'x');
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم'], ['image_path' => 'categories/keep.webp']);

        $this->putJson("/en/admin/categories/{$category->id}", $this->payload())->assertOk();

        $this->assertSame('categories/keep.webp', $category->fresh()->image_path);
        Storage::disk('public')->assertExists('categories/keep.webp');
    }

    /* ---------------------------------------------------------------- Toggle */

    #[DataProvider('locales')]
    public function test_the_active_switch_flips_the_flag_and_returns_the_text_status(string $locale): void
    {
        $this->signIn($locale);
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم']);

        $this->patchJson("/$locale/admin/categories/{$category->id}/active")
            ->assertOk()
            ->assertJson([
                'value' => false,
                'label' => __('admin.status.inactive', [], $locale),
                'message' => __('admin.messages.status_changed', ['status' => __('admin.status.inactive', [], $locale)], $locale),
            ]);
        $this->assertFalse($category->fresh()->is_active);

        $this->patchJson("/$locale/admin/categories/{$category->id}/active")
            ->assertOk()
            ->assertJson(['value' => true, 'label' => __('admin.status.active', [], $locale)]);
        $this->assertTrue($category->fresh()->is_active);
    }

    /* ---------------------------------------------------------------- Delete */

    public function test_delete_removes_the_category_its_translations_and_image(): void
    {
        $this->signIn();
        Storage::disk('public')->put('categories/drop.webp', 'x');
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم'], ['image_path' => 'categories/drop.webp']);

        $this->from('/en/admin/categories?status=active')
            ->delete("/en/admin/categories/{$category->id}")
            ->assertRedirect('/en/admin/categories?status=active')
            ->assertSessionHas('success', __('admin.messages.deleted'));

        $this->assertModelMissing($category);
        $this->assertSame(0, $category->translations()->count());
        Storage::disk('public')->assertMissing('categories/drop.webp');
    }

    public function test_the_success_flash_is_rendered_for_the_toast(): void
    {
        $this->signIn('ar');
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم']);

        $this->from('/ar/admin/categories')->followingRedirects()
            ->delete("/ar/admin/categories/{$category->id}")
            ->assertOk()
            ->assertSee('data-flash-message="'.__('admin.messages.deleted', [], 'ar').'" data-flash-type="success"', false);
    }

    public function test_the_delete_modal_names_the_item(): void
    {
        $this->signIn();
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم']);

        $this->get('/en/admin/categories')
            ->assertSee('data-name="Demo Rings"', false)
            ->assertSee('id="delete-modal-body"', false)
            ->assertSee('"deleteConfirm":', false);
    }

    /** @return array<string, array{string, int, int, string}> */
    public static function refusals(): array
    {
        // locale, attached products, of which trashed, expected message
        return [
            'en, one product' => ['en', 1, 0, 'This item is linked to 1 product, so it cannot be deleted. Deactivate it instead.'],
            'en, some trashed' => ['en', 5, 2, 'This item is linked to 5 products (2 of them are in the trash and still count), so it cannot be deleted. Deactivate it instead.'],
            'en, only trashed' => ['en', 1, 1, 'This item is linked to 1 product (1 of them is in the trash and still counts), so it cannot be deleted. Deactivate it instead.'],
            'ar, one product' => ['ar', 1, 0, 'لا يمكن حذف هذا العنصر لأنه مرتبط بمنتجات (منتج واحد). يمكنك إلغاء تفعيله بدلًا من ذلك.'],
            'ar, two products' => ['ar', 2, 0, 'لا يمكن حذف هذا العنصر لأنه مرتبط بمنتجات (منتجان). يمكنك إلغاء تفعيله بدلًا من ذلك.'],
            'ar, one trashed' => ['ar', 3, 1, 'لا يمكن حذف هذا العنصر لأنه مرتبط بمنتجات (3 منتجات، منها منتج واحد في سلة المحذوفات). يمكنك إلغاء تفعيله بدلًا من ذلك.'],
            'ar, some trashed' => ['ar', 5, 2, 'لا يمكن حذف هذا العنصر لأنه مرتبط بمنتجات (5 منتجات، منها منتجان في سلة المحذوفات). يمكنك إلغاء تفعيله بدلًا من ذلك.'],
            'ar, eleven products' => ['ar', 11, 3, 'لا يمكن حذف هذا العنصر لأنه مرتبط بمنتجات (11 منتجًا، منها 3 منتجات في سلة المحذوفات). يمكنك إلغاء تفعيله بدلًا من ذلك.'],
            'ar, many trashed' => ['ar', 12, 11, 'لا يمكن حذف هذا العنصر لأنه مرتبط بمنتجات (12 منتجًا، منها 11 منتجًا في سلة المحذوفات). يمكنك إلغاء تفعيله بدلًا من ذلك.'],
        ];
    }

    #[DataProvider('refusals')]
    public function test_a_refused_delete_explains_why_with_product_and_trash_counts(string $locale, int $products, int $trashed, string $message): void
    {
        $this->signIn($locale);
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم']);
        $attached = Product::factory()->count($products)->create();
        $category->products()->attach($attached);
        $attached->take($trashed)->each->delete();

        $this->from("/$locale/admin/categories")
            ->delete("/$locale/admin/categories/{$category->id}")
            ->assertRedirect("/$locale/admin/categories")
            ->assertSessionHas('error', $message)
            ->assertSessionMissing('success');

        $this->assertModelExists($category);

        // The layout hands the message to admin.js as a danger toast.
        $this->get("/$locale/admin/categories")
            ->assertSee('data-flash-message="'.e($message).'" data-flash-type="danger"', false);
    }

    public function test_a_refused_delete_requested_as_json_returns_409(): void
    {
        $this->signIn();
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم']);
        $category->products()->attach(Product::factory()->create());

        $this->deleteJson("/en/admin/categories/{$category->id}")
            ->assertStatus(409)
            ->assertExactJson(['message' => ModelInUseException::describe(1, 0)]);

        $this->assertModelExists($category);
    }

    public function test_a_refused_delete_is_not_reported_as_an_error(): void
    {
        $this->signIn();
        Exceptions::fake();
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم']);
        $category->products()->attach(Product::factory()->create());

        $this->from('/en/admin/categories')->delete("/en/admin/categories/{$category->id}")->assertRedirect();

        Exceptions::assertNothingReported();
    }

    /* ---------------------------------------------------------------- Access */

    /** @return array<string, array{string, string}> */
    public static function adminRoutes(): array
    {
        return [
            'index' => ['GET', 'categories'],
            'create' => ['GET', 'categories/create'],
            'store' => ['POST', 'categories'],
            'edit' => ['GET', 'categories/{id}/edit'],
            'update' => ['PUT', 'categories/{id}'],
            'toggle' => ['PATCH', 'categories/{id}/active'],
            'destroy' => ['DELETE', 'categories/{id}'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_are_redirected_to_the_localized_login(string $method, string $uri): void
    {
        foreach (['ar', 'en'] as $locale) {
            $this->refreshApplicationWithLocale($locale);
            $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم']);

            $this->call($method, "/$locale/admin/".str_replace('{id}', (string) $category->id, $uri), $this->payload())
                ->assertRedirect("http://localhost/$locale/admin/login");

            $this->assertModelExists($category);
            $this->assertSame(1, Category::count());
            $this->assertTrue($category->fresh()->is_active);
        }
    }

    #[DataProvider('adminRoutes')]
    public function test_users_of_the_web_guard_cannot_reach_the_admin(string $method, string $uri): void
    {
        $this->refreshApplicationWithLocale('en');
        $category = $this->category(['en' => 'Demo Rings', 'ar' => 'خواتم']);

        $this->actingAs(User::factory()->create())
            ->call($method, '/en/admin/'.str_replace('{id}', (string) $category->id, $uri), $this->payload())
            ->assertRedirect('http://localhost/en/admin/login');

        $this->assertSame(1, Category::count());
        $this->assertTrue($category->fresh()->is_active);
        $this->assertSame('Demo Rings', $category->fresh('translations')->translate('en')->name);
    }

    public function test_guests_asking_for_json_get_401(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->postJson('/en/admin/categories', $this->payload())->assertUnauthorized();
        $this->assertSame(0, Category::count());
    }
}
