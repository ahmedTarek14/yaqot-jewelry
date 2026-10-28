<?php

namespace Tests\Feature\Collection;

use App\Exceptions\ModelInUseException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\Admin;
use Modules\Collection\Models\Collection;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Collections admin: built like Categories, plus the "show on homepage" flag
 * (form switch, index toggle and filter).
 */
class CollectionAdminTest extends TestCase
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
            'name_ar' => 'مجموعة الزفاف',
            'name_en' => 'Demo Bridal',
            'description_ar' => 'وصف تجريبي.',
            'description_en' => 'A demo description.',
            'is_active' => '1',
            'show_on_homepage' => '1',
            'sort_order' => '3',
        ], $overrides);
    }

    /**
     * @param  array<string, string>  $names
     */
    private function collection(array $names, array $attributes = []): Collection
    {
        return Collection::factory()->create([
            ...$attributes,
            'en' => ['name' => $names['en']],
            'ar' => ['name' => $names['ar']],
        ]);
    }

    /* ----------------------------------------------------------------- Index */

    #[DataProvider('locales')]
    public function test_the_index_lists_collections_with_status_text_and_both_names(string $locale): void
    {
        $this->signIn($locale);
        $active = $this->collection(['en' => 'Demo Bridal', 'ar' => 'مجموعة الزفاف'], ['show_on_homepage' => true]);
        $inactive = $this->collection(['en' => 'Demo Ramadan', 'ar' => 'مجموعة رمضان'], ['is_active' => false]);
        $active->products()->attach(Product::factory()->count(2)->create());

        $this->get("/$locale/admin/collections")
            ->assertOk()
            ->assertSee('<html lang="'.$locale.'"', false)
            ->assertSee(__('collection::collection.title', [], $locale))
            ->assertSee('Demo Bridal')
            ->assertSee('مجموعة الزفاف')
            ->assertSee('Demo Ramadan')
            ->assertSee(__('admin.status.active', [], $locale))
            ->assertSee(__('admin.status.inactive', [], $locale))
            ->assertSee(__('collection::collection.homepage.on', [], $locale))
            ->assertSee(__('collection::collection.homepage.off', [], $locale))
            ->assertSee('data-toggle-url="'.route('admin.collections.toggle-active', $inactive).'"', false)
            ->assertSee('data-toggle-url="'.route('admin.collections.toggle-homepage', $inactive).'"', false)
            ->assertSee('data-url="'.route('admin.collections.destroy', $active).'"', false)
            ->assertSee('href="'.route('admin.collections.edit', $active).'"', false)
            ->assertSee('<td class="d-none d-md-table-cell">2</td>', false);
    }

    public function test_the_homepage_toggle_is_labelled_with_its_field(): void
    {
        $this->signIn();
        $this->collection(['en' => 'Demo Bridal', 'ar' => 'مجموعة الزفاف']);

        $this->get('/en/admin/collections')->assertOk()->assertSee(
            'aria-label="'.e(__('admin.status.toggle', ['field' => __('collection::collection.homepage.label'), 'name' => 'Demo Bridal'])).'"',
            false,
        );
    }

    public function test_the_index_searches_names_in_either_language(): void
    {
        $this->signIn();
        $this->collection(['en' => 'Demo Bridal', 'ar' => 'مجموعة الزفاف']);
        $this->collection(['en' => 'Demo Ramadan', 'ar' => 'مجموعة رمضان']);

        $this->get('/en/admin/collections?q=brid')->assertOk()->assertSee('Demo Bridal')->assertDontSee('Demo Ramadan');
        $this->get('/en/admin/collections?q='.urlencode('رمضان'))->assertOk()->assertSee('Demo Ramadan')->assertDontSee('Demo Bridal');
    }

    public function test_the_search_treats_like_wildcards_literally(): void
    {
        $this->signIn();
        $this->collection(['en' => 'Demo 100% Gold', 'ar' => 'ذهب تجريبي']);
        $this->collection(['en' => 'Demo Silverware', 'ar' => 'فضيات تجريبية']);

        $this->get('/en/admin/collections?q=%25')->assertOk()->assertSee('Demo 100% Gold')->assertDontSee('Demo Silverware');
        $this->get('/en/admin/collections?q=_')->assertOk()->assertSee(__('admin.listing.empty_filtered'));
    }

    public function test_the_index_filters_by_status(): void
    {
        $this->signIn();
        $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف']);
        $this->collection(['en' => 'Demo Ramadan', 'ar' => 'رمضان'], ['is_active' => false]);

        $this->get('/en/admin/collections?status=inactive')->assertOk()->assertSee('Demo Ramadan')->assertDontSee('Demo Bridal');
        $this->get('/en/admin/collections?status=active')->assertOk()->assertSee('Demo Bridal')->assertDontSee('Demo Ramadan');
        // An unknown value is ignored rather than failing.
        $this->get('/en/admin/collections?status=deleted')->assertOk()->assertSee('Demo Bridal')->assertSee('Demo Ramadan');
    }

    public function test_the_index_filters_by_homepage(): void
    {
        $this->signIn();
        $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف'], ['show_on_homepage' => true]);
        $this->collection(['en' => 'Demo Ramadan', 'ar' => 'رمضان']);

        $this->get('/en/admin/collections?homepage=yes')->assertOk()->assertSee('Demo Bridal')->assertDontSee('Demo Ramadan');
        $this->get('/en/admin/collections?homepage=no')->assertOk()->assertSee('Demo Ramadan')->assertDontSee('Demo Bridal');
        $this->get('/en/admin/collections?homepage=yes&status=inactive')->assertOk()->assertSee(__('admin.listing.empty_filtered'));
        // An unknown value is ignored rather than failing.
        $this->get('/en/admin/collections?homepage=maybe')->assertOk()->assertSee('Demo Bridal')->assertSee('Demo Ramadan');
    }

    public function test_the_index_sorts_by_allowlisted_columns_only(): void
    {
        $this->signIn();
        $this->collection(['en' => 'Demo Bravo', 'ar' => 'ب'], ['sort_order' => 1]);
        $this->collection(['en' => 'Demo Alpha', 'ar' => 'أ'], ['sort_order' => 2]);
        $this->collection(['en' => 'Demo Charlie', 'ar' => 'ج'], ['sort_order' => 3])
            ->products()->attach(Product::factory()->create());

        // Default: sort order.
        $this->get('/en/admin/collections')->assertSeeInOrder(['Demo Bravo', 'Demo Alpha', 'Demo Charlie']);
        $this->get('/en/admin/collections?sort=name')->assertSeeInOrder(['Demo Alpha', 'Demo Bravo', 'Demo Charlie']);
        $this->get('/en/admin/collections?sort=name&dir=desc')->assertSeeInOrder(['Demo Charlie', 'Demo Bravo', 'Demo Alpha']);
        $this->get('/en/admin/collections?sort=products_count&dir=desc')->assertSeeInOrder(['Demo Charlie', 'Demo Bravo']);
        // Not allowlisted: falls back to the default order instead of reaching the query.
        $this->get('/en/admin/collections?sort=show_on_homepage&dir=desc')->assertOk()->assertSeeInOrder(['Demo Bravo', 'Demo Alpha', 'Demo Charlie']);
        $this->get('/en/admin/collections?sort=id);drop table collections;--')->assertOk();
    }

    public function test_sorting_by_name_follows_the_admin_locale(): void
    {
        $this->signIn('ar');
        $this->collection(['en' => 'Demo Alpha', 'ar' => 'ياقوت تجريبي']);
        $this->collection(['en' => 'Demo Zulu', 'ar' => 'أساور تجريبية']);

        $this->get('/ar/admin/collections?sort=name')->assertOk()->assertSeeInOrder(['أساور تجريبية', 'ياقوت تجريبي']);
    }

    public function test_the_index_paginates_on_the_server_and_keeps_the_query_string(): void
    {
        $this->signIn();
        config(['catalog.admin.per_page' => 2]);
        foreach (range(1, 5) as $i) {
            $this->collection(['en' => "Demo Item $i", 'ar' => "عنصر $i"], ['sort_order' => $i]);
        }

        $this->get('/en/admin/collections?status=active')
            ->assertOk()
            ->assertSee('Demo Item 1')
            ->assertDontSee('Demo Item 3')
            ->assertSee('status=active&amp;page=2', false);

        $this->get('/en/admin/collections?page=3')->assertOk()->assertSee('Demo Item 5')->assertDontSee('Demo Item 1');
    }

    public function test_a_page_past_the_end_redirects_to_the_last_page(): void
    {
        $this->signIn();
        config(['catalog.admin.per_page' => 2]);
        foreach (range(1, 3) as $i) {
            $this->collection(['en' => "Demo Item $i", 'ar' => "عنصر $i"]);
        }

        $this->get('/en/admin/collections?homepage=no&page=9')
            ->assertRedirect('http://localhost/en/admin/collections?homepage=no&page=2');
    }

    #[DataProvider('locales')]
    public function test_the_index_has_empty_states(string $locale): void
    {
        $this->signIn($locale);

        $this->get("/$locale/admin/collections")
            ->assertOk()
            ->assertSee(__('collection::collection.empty', [], $locale))
            ->assertSee('href="'.route('admin.collections.create').'"', false);

        $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف']);

        $this->get("/$locale/admin/collections?q=nothing-matches")
            ->assertOk()
            ->assertSee(__('admin.listing.empty_filtered', [], $locale))
            ->assertDontSee(__('collection::collection.empty', [], $locale));
    }

    /* ------------------------------------------------------- Create and edit */

    #[DataProvider('locales')]
    public function test_the_create_page_has_the_sectioned_form_with_locale_tabs(string $locale): void
    {
        $this->signIn($locale);

        $response = $this->get("/$locale/admin/collections/create");

        // New collections start active and off the homepage.
        $this->assertMatchesRegularExpression('/name="is_active"\s+value="1" checked/', $response->getContent());
        $this->assertMatchesRegularExpression('/name="show_on_homepage"\s+value="1"\s+aria-describedby/', $response->getContent());

        $response->assertOk()
            ->assertSee(__('collection::collection.create', [], $locale))
            ->assertSee(__('collection::collection.fields.show_on_homepage', [], $locale))
            ->assertSee(__('collection::collection.fields.show_on_homepage_help', [], $locale))
            ->assertSee('class="ajax-form"', false)
            ->assertSee('action="'.route('admin.collections.store').'"', false)
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
        Storage::disk('public')->put('collections/demo.webp', 'x');
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'مجموعة الزفاف'], [
            'image_path' => 'collections/demo.webp',
            'is_active' => false,
            'show_on_homepage' => true,
        ]);

        $response = $this->get("/$locale/admin/collections/{$collection->id}/edit");

        $this->assertMatchesRegularExpression('/name="is_active"\s+value="1"\s+aria-describedby/', $response->getContent());
        $this->assertMatchesRegularExpression('/name="show_on_homepage"\s+value="1" checked/', $response->getContent());

        $response->assertOk()
            ->assertSee(__('collection::collection.edit', [], $locale))
            ->assertSee('action="'.route('admin.collections.update', $collection).'"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('value="Demo Bridal"', false)
            ->assertSee('value="مجموعة الزفاف"', false)
            ->assertSee(Storage::disk('public')->url('collections/demo.webp'))
            ->assertSee('name="remove_image"', false);
    }

    public function test_an_unknown_collection_returns_404(): void
    {
        $this->signIn();

        $this->get('/en/admin/collections/999/edit')->assertNotFound();
        $this->putJson('/en/admin/collections/999', $this->payload())->assertNotFound();
        $this->patchJson('/en/admin/collections/999/homepage')->assertNotFound();
        $this->delete('/en/admin/collections/999')->assertNotFound();
    }

    /* ----------------------------------------------------------------- Store */

    #[DataProvider('locales')]
    public function test_store_creates_the_collection_with_both_translations(string $locale): void
    {
        $this->signIn($locale);

        $this->postJson("/$locale/admin/collections", $this->payload())
            ->assertOk()
            ->assertExactJson([
                'message' => __('admin.messages.saved', [], $locale),
                'redirect' => "http://localhost/$locale/admin/collections",
            ]);

        $collection = Collection::with('translations')->sole();
        $this->assertSame('Demo Bridal', $collection->translate('en')->name);
        $this->assertSame('مجموعة الزفاف', $collection->translate('ar')->name);
        $this->assertSame('A demo description.', $collection->translate('en')->description);
        $this->assertSame('demo-bridal', $collection->slug);
        $this->assertTrue($collection->is_active);
        $this->assertTrue($collection->show_on_homepage);
        $this->assertSame(3, $collection->sort_order);
        $this->assertNull($collection->image_path);
    }

    public function test_store_accepts_empty_optional_fields(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/collections', $this->payload([
            'description_ar' => '',
            'description_en' => '',
            'is_active' => '0',
            'show_on_homepage' => '0',
        ]))->assertOk();

        $collection = Collection::with('translations')->sole();
        $this->assertNull($collection->translate('en')->description);
        $this->assertFalse($collection->is_active);
        $this->assertFalse($collection->show_on_homepage);
    }

    public function test_store_with_an_image_saves_a_resized_webp(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/collections', $this->payload(['image' => UploadedFile::fake()->image('photo.jpg', 2400, 1800)]))
            ->assertOk();

        $path = Collection::sole()->image_path;
        $this->assertMatchesRegularExpression('#^collections/[0-9a-f-]{36}\.webp$#', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame([1600, 1200], array_slice(getimagesizefromstring(Storage::disk('public')->get($path)), 0, 2));
    }

    /* ------------------------------------------------------------ Validation */

    #[DataProvider('locales')]
    public function test_validation_returns_422_json_with_every_error(string $locale): void
    {
        $this->signIn($locale);

        $response = $this->postJson("/$locale/admin/collections", [
            'name_ar' => '',
            'name_en' => str_repeat('a', 256),
            'description_en' => ['not', 'a', 'string'],
            'show_on_homepage' => 'sometimes',
            'sort_order' => '-1',
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors(['name_ar', 'name_en', 'description_en', 'is_active', 'show_on_homepage', 'sort_order'])
            ->assertJsonMissingValidationErrors(['description_ar', 'image', 'remove_image']);

        // Messages are in the admin's language and name the field's language tab.
        $nameAr = __('collection::collection.fields.name', [], $locale).' ('.__('admin.form.language_names.ar', [], $locale).')';
        $this->assertSame(
            __('validation.required', ['attribute' => $nameAr], $locale),
            $response->json('errors.name_ar.0'),
        );

        $this->assertSame(0, Collection::count());
    }

    public function test_every_failing_rule_of_a_field_is_reported(): void
    {
        $this->signIn();

        // Not an image and too large: both messages come back, not just the first.
        $this->postJson('/en/admin/collections', $this->payload([
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
            'too short' => [fn () => UploadedFile::fake()->image('strip.png', 400, 199)],
            'too wide' => [fn () => UploadedFile::fake()->image('panorama.png', 6001, 300)],
            'too tall' => [fn () => UploadedFile::fake()->image('banner.png', 300, 6001)],
        ];
    }

    #[DataProvider('invalidImages')]
    public function test_invalid_images_are_rejected_and_nothing_is_stored(\Closure $file): void
    {
        $this->signIn();

        $this->postJson('/en/admin/collections', $this->payload(['image' => $file()]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);

        $this->assertSame(0, Collection::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_images_at_the_limits_are_accepted(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/collections', $this->payload([
            'name_en' => 'Demo Limit A',
            'image' => UploadedFile::fake()->image('min.png', 200, 200)->size(5120),
        ]))->assertOk();

        $this->postJson('/en/admin/collections', $this->payload([
            'name_en' => 'Demo Limit B',
            'image' => UploadedFile::fake()->image('max.webp', 6000, 200),
        ]))->assertOk();

        $this->assertCount(2, Storage::disk('public')->allFiles('collections'));
    }

    /* ---------------------------------------------------------------- Update */

    public function test_update_changes_fields_and_translations_but_never_the_slug(): void
    {
        $this->signIn();
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف'], ['show_on_homepage' => true]);

        $this->putJson("/en/admin/collections/{$collection->id}", $this->payload([
            'name_en' => 'Demo Bridal Edit',
            'name_ar' => 'زفاف فاخر',
            'is_active' => '0',
            'show_on_homepage' => '0',
            'sort_order' => '9',
        ]))->assertOk()->assertJsonPath('redirect', 'http://localhost/en/admin/collections');

        $collection = $collection->fresh('translations');
        $this->assertSame('Demo Bridal Edit', $collection->translate('en')->name);
        $this->assertSame('زفاف فاخر', $collection->translate('ar')->name);
        $this->assertSame('demo-bridal', $collection->slug);
        $this->assertFalse($collection->is_active);
        $this->assertFalse($collection->show_on_homepage);
        $this->assertSame(9, $collection->sort_order);
        $this->assertSame(2, $collection->translations()->count());
    }

    public function test_update_validation_returns_422_and_changes_nothing(): void
    {
        $this->signIn();
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف']);

        $this->putJson("/en/admin/collections/{$collection->id}", $this->payload(['name_en' => '   ']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name_en']);

        $this->assertSame('Demo Bridal', $collection->fresh('translations')->translate('en')->name);
    }

    public function test_replacing_the_image_deletes_the_old_file(): void
    {
        $this->signIn();
        Storage::disk('public')->put('collections/old.webp', 'x');
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف'], ['image_path' => 'collections/old.webp']);

        $this->putJson("/en/admin/collections/{$collection->id}", $this->payload([
            'image' => UploadedFile::fake()->image('new.png', 500, 500),
        ]))->assertOk();

        $new = $collection->fresh()->image_path;
        $this->assertNotSame('collections/old.webp', $new);
        $this->assertStringStartsWith('collections/', $new);
        Storage::disk('public')->assertExists($new);
        Storage::disk('public')->assertMissing('collections/old.webp');
    }

    public function test_removing_the_image_deletes_the_file(): void
    {
        $this->signIn();
        Storage::disk('public')->put('collections/old.webp', 'x');
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف'], ['image_path' => 'collections/old.webp']);

        $this->putJson("/en/admin/collections/{$collection->id}", $this->payload(['remove_image' => '1']))->assertOk();

        $this->assertNull($collection->fresh()->image_path);
        Storage::disk('public')->assertMissing('collections/old.webp');
    }

    public function test_saving_without_a_new_image_keeps_the_current_one(): void
    {
        $this->signIn();
        Storage::disk('public')->put('collections/keep.webp', 'x');
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف'], ['image_path' => 'collections/keep.webp']);

        $this->putJson("/en/admin/collections/{$collection->id}", $this->payload())->assertOk();

        $this->assertSame('collections/keep.webp', $collection->fresh()->image_path);
        Storage::disk('public')->assertExists('collections/keep.webp');
    }

    /* --------------------------------------------------------------- Toggles */

    #[DataProvider('locales')]
    public function test_the_active_switch_flips_the_flag_and_returns_the_text_status(string $locale): void
    {
        $this->signIn($locale);
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف']);

        $this->patchJson("/$locale/admin/collections/{$collection->id}/active")
            ->assertOk()
            ->assertJson([
                'value' => false,
                'label' => __('admin.status.inactive', [], $locale),
                'message' => __('admin.messages.status_changed', ['status' => __('admin.status.inactive', [], $locale)], $locale),
            ]);
        $this->assertFalse($collection->fresh()->is_active);

        $this->patchJson("/$locale/admin/collections/{$collection->id}/active")
            ->assertOk()
            ->assertJson(['value' => true, 'label' => __('admin.status.active', [], $locale)]);
        $this->assertTrue($collection->fresh()->is_active);
    }

    #[DataProvider('locales')]
    public function test_the_homepage_switch_flips_the_flag_and_returns_the_text_status(string $locale): void
    {
        $this->signIn($locale);
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف']);
        $on = __('collection::collection.homepage.on', [], $locale);

        $this->patchJson("/$locale/admin/collections/{$collection->id}/homepage")
            ->assertOk()
            ->assertJson([
                'value' => true,
                'label' => $on,
                'message' => __('admin.messages.status_changed', ['status' => $on], $locale),
            ]);
        $this->assertTrue($collection->fresh()->show_on_homepage);
        $this->assertTrue($collection->fresh()->is_active);

        $this->patchJson("/$locale/admin/collections/{$collection->id}/homepage")
            ->assertOk()
            ->assertJson(['value' => false, 'label' => __('collection::collection.homepage.off', [], $locale)]);
        $this->assertFalse($collection->fresh()->show_on_homepage);
    }

    /* ---------------------------------------------------------------- Delete */

    public function test_delete_removes_the_collection_its_translations_and_image(): void
    {
        $this->signIn();
        Storage::disk('public')->put('collections/drop.webp', 'x');
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف'], ['image_path' => 'collections/drop.webp']);

        $this->from('/en/admin/collections?status=active')
            ->delete("/en/admin/collections/{$collection->id}")
            ->assertRedirect('/en/admin/collections?status=active')
            ->assertSessionHas('success', __('admin.messages.deleted'));

        $this->assertModelMissing($collection);
        $this->assertSame(0, $collection->translations()->count());
        Storage::disk('public')->assertMissing('collections/drop.webp');
    }

    public function test_the_success_flash_is_rendered_for_the_toast(): void
    {
        $this->signIn('ar');
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف']);

        $this->from('/ar/admin/collections')->followingRedirects()
            ->delete("/ar/admin/collections/{$collection->id}")
            ->assertOk()
            ->assertSee('data-flash-message="'.__('admin.messages.deleted', [], 'ar').'" data-flash-type="success"', false);
    }

    public function test_the_delete_modal_names_the_item(): void
    {
        $this->signIn();
        $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف']);

        $this->get('/en/admin/collections')
            ->assertSee('data-name="Demo Bridal"', false)
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
            'ar, one trashed' => ['ar', 3, 1, 'لا يمكن حذف هذا العنصر لأنه مرتبط بمنتجات (3 منتجات، منها منتج واحد في سلة المحذوفات). يمكنك إلغاء تفعيله بدلًا من ذلك.'],
            'ar, only trashed' => ['ar', 2, 2, 'لا يمكن حذف هذا العنصر لأنه مرتبط بمنتجات (منتجان، منها منتجان في سلة المحذوفات). يمكنك إلغاء تفعيله بدلًا من ذلك.'],
        ];
    }

    #[DataProvider('refusals')]
    public function test_a_refused_delete_explains_why_with_product_and_trash_counts(string $locale, int $products, int $trashed, string $message): void
    {
        $this->signIn($locale);
        Storage::disk('public')->put('collections/keep.webp', 'x');
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف'], ['image_path' => 'collections/keep.webp']);
        $attached = Product::factory()->count($products)->create();
        $collection->products()->attach($attached);
        $attached->take($trashed)->each->delete();

        $this->from("/$locale/admin/collections")
            ->delete("/$locale/admin/collections/{$collection->id}")
            ->assertRedirect("/$locale/admin/collections")
            ->assertSessionHas('error', $message)
            ->assertSessionMissing('success');

        $this->assertModelExists($collection);
        Storage::disk('public')->assertExists('collections/keep.webp');

        // The layout hands the message to admin.js as a danger toast.
        $this->get("/$locale/admin/collections")
            ->assertSee('data-flash-message="'.e($message).'" data-flash-type="danger"', false);
    }

    public function test_a_refused_delete_requested_as_json_returns_409(): void
    {
        $this->signIn();
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف']);
        $collection->products()->attach(Product::factory()->create());

        $this->deleteJson("/en/admin/collections/{$collection->id}")
            ->assertStatus(409)
            ->assertExactJson(['message' => ModelInUseException::describe(1, 0)]);

        $this->assertModelExists($collection);
    }

    public function test_a_refused_delete_is_not_reported_as_an_error(): void
    {
        $this->signIn();
        Exceptions::fake();
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف']);
        $collection->products()->attach(Product::factory()->create());

        $this->from('/en/admin/collections')->delete("/en/admin/collections/{$collection->id}")->assertRedirect();

        Exceptions::assertNothingReported();
    }

    /* ---------------------------------------------------------------- Access */

    /** @return array<string, array{string, string}> */
    public static function adminRoutes(): array
    {
        return [
            'index' => ['GET', 'collections'],
            'create' => ['GET', 'collections/create'],
            'store' => ['POST', 'collections'],
            'edit' => ['GET', 'collections/{id}/edit'],
            'update' => ['PUT', 'collections/{id}'],
            'toggle active' => ['PATCH', 'collections/{id}/active'],
            'toggle homepage' => ['PATCH', 'collections/{id}/homepage'],
            'destroy' => ['DELETE', 'collections/{id}'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_are_redirected_to_the_localized_login(string $method, string $uri): void
    {
        foreach (['ar', 'en'] as $locale) {
            $this->refreshApplicationWithLocale($locale);
            $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف']);

            $this->call($method, "/$locale/admin/".str_replace('{id}', (string) $collection->id, $uri), $this->payload())
                ->assertRedirect("http://localhost/$locale/admin/login");

            $this->assertModelExists($collection);
            $this->assertSame(1, Collection::count());
            $this->assertTrue($collection->fresh()->is_active);
            $this->assertFalse($collection->fresh()->show_on_homepage);
        }
    }

    #[DataProvider('adminRoutes')]
    public function test_users_of_the_web_guard_cannot_reach_the_admin(string $method, string $uri): void
    {
        $this->refreshApplicationWithLocale('en');
        $collection = $this->collection(['en' => 'Demo Bridal', 'ar' => 'زفاف']);

        $this->actingAs(User::factory()->create())
            ->call($method, '/en/admin/'.str_replace('{id}', (string) $collection->id, $uri), $this->payload())
            ->assertRedirect('http://localhost/en/admin/login');

        $this->assertSame(1, Collection::count());
        $this->assertTrue($collection->fresh()->is_active);
        $this->assertFalse($collection->fresh()->show_on_homepage);
        $this->assertSame('Demo Bridal', $collection->fresh('translations')->translate('en')->name);
    }

    public function test_guests_asking_for_json_get_401(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->postJson('/en/admin/collections', $this->payload())->assertUnauthorized();
        $this->assertSame(0, Collection::count());
    }
}
