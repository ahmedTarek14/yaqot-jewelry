<?php

namespace Tests\Feature\Branch;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\Admin;
use Modules\Branch\Models\Branch;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Branches admin, built on the shared admin building blocks like Categories,
 * plus Egyptian phone / WhatsApp normalization and https-only map links.
 */
class BranchAdminTest extends TestCase
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
            'name_ar' => 'فرع تجريبي',
            'name_en' => 'Demo Branch',
            'address_ar' => 'عنوان تجريبي',
            'address_en' => 'Demo address',
            'working_hours_ar' => 'مواعيد تجريبية',
            'working_hours_en' => 'Demo hours',
            'phone' => '0223456789',
            'whatsapp' => '01000000001',
            'map_url' => 'https://maps.example.com/demo',
            'is_active' => '1',
            'sort_order' => '3',
        ], $overrides);
    }

    /**
     * @param  array<string, string>  $names
     * @param  array<string, mixed>  $attributes
     */
    private function branch(array $names, array $attributes = []): Branch
    {
        return Branch::factory()->create([
            ...$attributes,
            'en' => ['name' => $names['en'], 'address' => 'Demo address'],
            'ar' => ['name' => $names['ar'], 'address' => 'عنوان تجريبي'],
        ]);
    }

    /* ----------------------------------------------------------------- Index */

    #[DataProvider('locales')]
    public function test_the_index_lists_branches_with_status_phone_and_both_names(string $locale): void
    {
        $this->signIn($locale);
        $active = $this->branch(['en' => 'Demo Downtown', 'ar' => 'فرع وسط تجريبي'], ['phone' => '0223456789']);
        $inactive = $this->branch(['en' => 'Demo Uptown', 'ar' => 'فرع شمالي تجريبي'], ['is_active' => false]);

        $this->get("/$locale/admin/branches")
            ->assertOk()
            ->assertSee('<html lang="'.$locale.'"', false)
            ->assertSee(__('branch::branch.title', [], $locale))
            ->assertSee('Demo Downtown')
            ->assertSee('فرع وسط تجريبي')
            ->assertSee('Demo Uptown')
            ->assertSee(__('admin.status.active', [], $locale))
            ->assertSee(__('admin.status.inactive', [], $locale))
            ->assertSee('<td class="d-none d-md-table-cell"><bdi dir="ltr">0223456789</bdi></td>', false)
            ->assertSee('data-toggle-url="'.route('admin.branches.toggle-active', $inactive).'"', false)
            ->assertSee('data-url="'.route('admin.branches.destroy', $active).'"', false)
            ->assertSee('href="'.route('admin.branches.edit', $active).'"', false);
    }

    public function test_the_index_searches_names_in_either_language_and_the_phone(): void
    {
        $this->signIn();
        $this->branch(['en' => 'Demo Downtown', 'ar' => 'فرع وسط تجريبي'], ['phone' => '0223456789']);
        $this->branch(['en' => 'Demo Seaside', 'ar' => 'فرع ساحلي تجريبي'], ['phone' => '034567890']);

        $this->get('/en/admin/branches?q=down')->assertOk()->assertSee('Demo Downtown')->assertDontSee('Demo Seaside');
        $this->get('/en/admin/branches?q='.urlencode('ساحلي'))->assertOk()->assertSee('Demo Seaside')->assertDontSee('Demo Downtown');
        $this->get('/en/admin/branches?q=03456')->assertOk()->assertSee('Demo Seaside')->assertDontSee('Demo Downtown');
    }

    public function test_the_search_finds_a_phone_typed_in_another_form(): void
    {
        $this->signIn();
        $this->branch(['en' => 'Demo Cairo', 'ar' => 'القاهرة'], ['phone' => '0223456789']);
        $this->branch(['en' => 'Demo Giza', 'ar' => 'الجيزة'], ['phone' => '01012345678']);

        foreach (['+20 2 2345 6789', '(02) 2345-6789', '٠٢٢٣٤٥٦٧٨٩'] as $typed) {
            $this->get('/en/admin/branches?q='.urlencode($typed))
                ->assertOk()->assertSee('Demo Cairo')->assertDontSee('Demo Giza');
        }
    }

    public function test_the_search_treats_like_wildcards_literally(): void
    {
        $this->signIn();
        $this->branch(['en' => 'Demo 100% Branch', 'ar' => 'فرع'], ['phone' => '0223456789']);
        $this->branch(['en' => 'Demo Other', 'ar' => 'آخر'], ['phone' => '0223456780']);

        $this->get('/en/admin/branches?q=%25')->assertOk()->assertSee('Demo 100% Branch')->assertDontSee('Demo Other');
        $this->get('/en/admin/branches?q=_')->assertOk()->assertSee(__('admin.listing.empty_filtered'));
    }

    public function test_the_index_filters_by_status(): void
    {
        $this->signIn();
        $this->branch(['en' => 'Demo Downtown', 'ar' => 'وسط']);
        $this->branch(['en' => 'Demo Uptown', 'ar' => 'شمال'], ['is_active' => false]);

        $this->get('/en/admin/branches?status=inactive')->assertOk()->assertSee('Demo Uptown')->assertDontSee('Demo Downtown');
        $this->get('/en/admin/branches?status=active')->assertOk()->assertSee('Demo Downtown')->assertDontSee('Demo Uptown');
        $this->get('/en/admin/branches?status=deleted')->assertOk()->assertSee('Demo Downtown')->assertSee('Demo Uptown');
    }

    public function test_the_index_sorts_by_allowlisted_columns_only(): void
    {
        $this->signIn();
        $this->branch(['en' => 'Demo Bravo', 'ar' => 'ب'], ['sort_order' => 1]);
        $this->branch(['en' => 'Demo Alpha', 'ar' => 'أ'], ['sort_order' => 2]);
        $this->branch(['en' => 'Demo Charlie', 'ar' => 'ج'], ['sort_order' => 3]);

        $this->get('/en/admin/branches')->assertSeeInOrder(['Demo Bravo', 'Demo Alpha', 'Demo Charlie']);
        $this->get('/en/admin/branches?sort=name')->assertSeeInOrder(['Demo Alpha', 'Demo Bravo', 'Demo Charlie']);
        $this->get('/en/admin/branches?sort=name&dir=desc')->assertSeeInOrder(['Demo Charlie', 'Demo Bravo', 'Demo Alpha']);
        $this->get('/en/admin/branches?sort=sort_order&dir=desc')->assertSeeInOrder(['Demo Charlie', 'Demo Alpha', 'Demo Bravo']);
        $this->get('/en/admin/branches?sort=phone&dir=desc')->assertOk()->assertSeeInOrder(['Demo Bravo', 'Demo Alpha', 'Demo Charlie']);
        $this->get('/en/admin/branches?sort=id);drop table branches;--')->assertOk();
    }

    public function test_a_page_past_the_end_redirects_to_the_last_page(): void
    {
        $this->signIn();
        config(['catalog.admin.per_page' => 2]);
        foreach (range(1, 3) as $i) {
            $this->branch(['en' => "Demo Item $i", 'ar' => "عنصر $i"]);
        }

        $this->get('/en/admin/branches?status=active&page=9')
            ->assertRedirect('http://localhost/en/admin/branches?status=active&page=2');
    }

    #[DataProvider('locales')]
    public function test_the_index_has_empty_states(string $locale): void
    {
        $this->signIn($locale);

        $this->get("/$locale/admin/branches")
            ->assertOk()
            ->assertSee(__('branch::branch.empty', [], $locale))
            ->assertSee('href="'.route('admin.branches.create').'"', false);

        $this->branch(['en' => 'Demo Downtown', 'ar' => 'وسط']);

        $this->get("/$locale/admin/branches?q=nothing-matches")
            ->assertOk()
            ->assertSee(__('admin.listing.empty_filtered', [], $locale))
            ->assertDontSee(__('branch::branch.empty', [], $locale));
    }

    /* ------------------------------------------------------- Create and edit */

    #[DataProvider('locales')]
    public function test_the_create_page_has_the_sectioned_form_with_locale_tabs(string $locale): void
    {
        $this->signIn($locale);

        $response = $this->get("/$locale/admin/branches/create");

        $this->assertMatchesRegularExpression('/name="is_active"\s+value="1" checked/', $response->getContent());

        $response->assertOk()
            ->assertSee(__('branch::branch.create', [], $locale))
            ->assertSee('class="ajax-form"', false)
            ->assertSee('action="'.route('admin.branches.store').'"', false)
            ->assertSeeInOrder(['data-bs-target="#translations-ar"', 'data-bs-target="#translations-en"'], false)
            ->assertSee('name="name_ar"', false)
            ->assertSee('name="address_en"', false)
            ->assertSee('name="working_hours_ar"', false)
            ->assertSee(__('branch::branch.fields.working_hours_help', [], $locale))
            ->assertSee(__('branch::branch.fields.map_url_help', [], $locale))
            ->assertSee('name="sort_order"', false)
            ->assertDontSee('name="_method" value="PUT"', false);

        // Numbers and links are typed left to right, with a suitable keyboard.
        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/inputmode="tel" dir="ltr"[^>]*type="tel" id="field-phone"/', $content);
        $this->assertMatchesRegularExpression('/inputmode="tel" dir="ltr"[^>]*type="tel" id="field-whatsapp"/', $content);
        $this->assertMatchesRegularExpression('/inputmode="url" dir="ltr"[^>]*type="url" id="field-map_url"/', $content);
    }

    #[DataProvider('locales')]
    public function test_the_edit_page_is_filled_with_both_translations_and_contacts(string $locale): void
    {
        $this->signIn($locale);
        $branch = $this->branch(['en' => 'Demo Downtown', 'ar' => 'فرع وسط تجريبي'], [
            'phone' => '0223456789',
            'whatsapp' => '201000000001',
            'map_url' => 'https://maps.example.com/demo',
            'is_active' => false,
        ]);

        $response = $this->get("/$locale/admin/branches/{$branch->id}/edit");

        $this->assertMatchesRegularExpression('/name="is_active"\s+value="1"\s+aria-describedby/', $response->getContent());

        $response->assertOk()
            ->assertSee(__('branch::branch.edit', [], $locale))
            ->assertSee('action="'.route('admin.branches.update', $branch).'"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('value="Demo Downtown"', false)
            ->assertSee('value="فرع وسط تجريبي"', false)
            ->assertSee('value="0223456789"', false)
            ->assertSee('value="201000000001"', false)
            ->assertSee('value="https://maps.example.com/demo"', false);
    }

    public function test_an_unknown_branch_returns_404(): void
    {
        $this->signIn();

        $this->get('/en/admin/branches/999/edit')->assertNotFound();
        $this->putJson('/en/admin/branches/999', $this->payload())->assertNotFound();
        $this->delete('/en/admin/branches/999')->assertNotFound();
    }

    /* ----------------------------------------------------------------- Store */

    #[DataProvider('locales')]
    public function test_store_creates_the_branch_with_both_translations(string $locale): void
    {
        $this->signIn($locale);

        $this->postJson("/$locale/admin/branches", $this->payload())
            ->assertOk()
            ->assertExactJson([
                'message' => __('admin.messages.saved', [], $locale),
                'redirect' => "http://localhost/$locale/admin/branches",
            ]);

        $branch = Branch::with('translations')->sole();
        $this->assertSame('Demo Branch', $branch->translate('en')->name);
        $this->assertSame('فرع تجريبي', $branch->translate('ar')->name);
        $this->assertSame('Demo address', $branch->translate('en')->address);
        $this->assertSame('مواعيد تجريبية', $branch->translate('ar')->working_hours);
        $this->assertSame('0223456789', $branch->phone);
        $this->assertSame('201000000001', $branch->whatsapp);
        $this->assertSame('https://maps.example.com/demo', $branch->map_url);
        $this->assertTrue($branch->is_active);
        $this->assertSame(3, $branch->sort_order);
    }

    public function test_store_accepts_empty_optional_fields(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/branches', $this->payload([
            'working_hours_ar' => '',
            'working_hours_en' => '',
            'whatsapp' => '',
            'map_url' => '',
            'is_active' => '0',
        ]))->assertOk();

        $branch = Branch::with('translations')->sole();
        $this->assertNull($branch->translate('en')->working_hours);
        $this->assertNull($branch->whatsapp);
        $this->assertNull($branch->map_url);
        $this->assertFalse($branch->is_active);
    }

    /** @return array<string, array{string, string}> */
    public static function validPhones(): array
    {
        return [
            'cairo landline' => ['0223456789', '0223456789'],
            'cairo landline, international' => ['+20 2 2345 6789', '0223456789'],
            'alexandria landline' => ['03-456-7890', '034567890'],
            'governorate landline' => ['(040) 345 6789', '0403456789'],
            'mobile' => ['01012345678', '01012345678'],
            'mobile, international' => ['+201112345678', '01112345678'],
            'mobile, 0020' => ['00201512345678', '01512345678'],
            'mobile, arabic digits' => ['٠١٢٢٣٤٥٦٧٨٩', '01223456789'],
            'hotline 19xxx' => ['19123', '19123'],
            'hotline 16xxx, arabic digits' => ['١٦٧٨٩', '16789'],
        ];
    }

    #[DataProvider('validPhones')]
    public function test_landline_and_mobile_phones_are_accepted_and_stored_normalized(string $input, string $stored): void
    {
        $this->signIn();

        $this->postJson('/en/admin/branches', $this->payload(['phone' => $input]))->assertOk();

        $this->assertSame($stored, Branch::sole()->phone);
    }

    /** @return array<string, array{string}> */
    public static function invalidPhones(): array
    {
        return [
            'too short' => ['0223'],
            'letters' => ['02234abc89'],
            'foreign' => ['+44 20 7946 0000'],
            'unknown prefix' => ['01712345678'],
            'too long landline' => ['02234567890'],
            'hotline not starting with 1' => ['29123'],
            'six-digit hotline' => ['191234'],
        ];
    }

    #[DataProvider('invalidPhones')]
    public function test_invalid_phones_are_rejected(string $input): void
    {
        $this->signIn();

        $this->postJson('/en/admin/branches', $this->payload(['phone' => $input]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone' => __('admin.validation.egyptian_phone', ['attribute' => __('branch::branch.fields.phone')])]);

        $this->assertSame(0, Branch::count());
    }

    public function test_whatsapp_accepts_mobiles_only_and_is_stored_international(): void
    {
        $this->signIn();

        $this->postJson('/en/admin/branches', $this->payload(['whatsapp' => '0223456789']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['whatsapp' => __('admin.validation.egyptian_mobile', ['attribute' => __('branch::branch.fields.whatsapp')])])
            ->assertJsonMissingValidationErrors(['phone']);

        $this->postJson('/en/admin/branches', $this->payload(['whatsapp' => '19123']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['whatsapp']);

        $this->postJson('/en/admin/branches', $this->payload(['whatsapp' => '+20 115 123 4567']))->assertOk();

        $this->assertSame('201151234567', Branch::sole()->whatsapp);
    }

    public function test_the_map_link_must_be_https(): void
    {
        $this->signIn();

        foreach (['http://maps.example.com/demo', 'javascript:alert(1)', 'not a url', 'https://'.str_repeat('a', 2040).'.com'] as $url) {
            $this->postJson('/en/admin/branches', $this->payload(['map_url' => $url]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['map_url']);
        }

        $this->assertSame(0, Branch::count());

        $this->postJson('/en/admin/branches', $this->payload(['map_url' => 'https://maps.example.com/?q=demo']))->assertOk();
        $this->assertSame('https://maps.example.com/?q=demo', Branch::sole()->map_url);
    }

    /* ------------------------------------------------------------ Validation */

    #[DataProvider('locales')]
    public function test_validation_returns_422_json_with_every_error(string $locale): void
    {
        $this->signIn($locale);

        $response = $this->postJson("/$locale/admin/branches", [
            'name_ar' => '',
            'name_en' => str_repeat('a', 256),
            'address_en' => str_repeat('a', 501),
            'working_hours_ar' => str_repeat('a', 501),
            'whatsapp' => '0223456789',
            'map_url' => 'http://maps.example.com',
            'sort_order' => '-1',
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors([
                'name_ar', 'name_en', 'address_ar', 'address_en', 'working_hours_ar',
                'phone', 'whatsapp', 'map_url', 'is_active', 'sort_order',
            ])
            ->assertJsonMissingValidationErrors(['working_hours_en']);

        $addressAr = __('branch::branch.fields.address', [], $locale).' ('.__('admin.form.language_names.ar', [], $locale).')';
        $this->assertSame(
            __('validation.required', ['attribute' => $addressAr], $locale),
            $response->json('errors.address_ar.0'),
        );
        $this->assertSame(
            __('admin.validation.egyptian_mobile', ['attribute' => __('branch::branch.fields.whatsapp', [], $locale)], $locale),
            $response->json('errors.whatsapp.0'),
        );

        $this->assertSame(0, Branch::count());
    }

    /* ---------------------------------------------------------------- Update */

    public function test_update_changes_fields_and_translations(): void
    {
        $this->signIn();
        $branch = $this->branch(['en' => 'Demo Downtown', 'ar' => 'وسط']);

        $this->putJson("/en/admin/branches/{$branch->id}", $this->payload([
            'name_en' => 'Demo Riverside',
            'name_ar' => 'فرع النهر',
            'phone' => '+20 10 1234 5678',
            'whatsapp' => '',
            'map_url' => '',
            'is_active' => '0',
            'sort_order' => '9',
        ]))->assertOk()->assertJsonPath('redirect', 'http://localhost/en/admin/branches');

        $branch = $branch->fresh('translations');
        $this->assertSame('Demo Riverside', $branch->translate('en')->name);
        $this->assertSame('فرع النهر', $branch->translate('ar')->name);
        $this->assertSame('01012345678', $branch->phone);
        $this->assertNull($branch->whatsapp);
        $this->assertNull($branch->map_url);
        $this->assertFalse($branch->is_active);
        $this->assertSame(9, $branch->sort_order);
        $this->assertSame(2, $branch->translations()->count());
    }

    public function test_update_validation_returns_422_and_changes_nothing(): void
    {
        $this->signIn();
        $branch = $this->branch(['en' => 'Demo Downtown', 'ar' => 'وسط'], ['phone' => '0223456789']);

        $this->putJson("/en/admin/branches/{$branch->id}", $this->payload(['name_en' => '   ', 'phone' => '123']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name_en', 'phone']);

        $branch = $branch->fresh('translations');
        $this->assertSame('Demo Downtown', $branch->translate('en')->name);
        $this->assertSame('0223456789', $branch->phone);
    }

    /* ---------------------------------------------------------------- Toggle */

    #[DataProvider('locales')]
    public function test_the_active_switch_flips_the_flag_and_returns_the_text_status(string $locale): void
    {
        $this->signIn($locale);
        $branch = $this->branch(['en' => 'Demo Downtown', 'ar' => 'وسط']);

        $this->patchJson("/$locale/admin/branches/{$branch->id}/active")
            ->assertOk()
            ->assertJson([
                'value' => false,
                'label' => __('admin.status.inactive', [], $locale),
                'message' => __('admin.messages.status_changed', ['status' => __('admin.status.inactive', [], $locale)], $locale),
            ]);
        $this->assertFalse($branch->fresh()->is_active);

        $this->patchJson("/$locale/admin/branches/{$branch->id}/active")
            ->assertOk()
            ->assertJson(['value' => true, 'label' => __('admin.status.active', [], $locale)]);
        $this->assertTrue($branch->fresh()->is_active);
    }

    /* ---------------------------------------------------------------- Delete */

    public function test_delete_removes_the_branch_and_its_translations(): void
    {
        $this->signIn();
        $branch = $this->branch(['en' => 'Demo Downtown', 'ar' => 'وسط']);

        $this->from('/en/admin/branches?status=active')
            ->delete("/en/admin/branches/{$branch->id}")
            ->assertRedirect('/en/admin/branches?status=active')
            ->assertSessionHas('success', __('admin.messages.deleted'));

        $this->assertModelMissing($branch);
        $this->assertSame(0, $branch->translations()->count());
    }

    public function test_the_delete_modal_names_the_item(): void
    {
        $this->signIn();
        $this->branch(['en' => 'Demo Downtown', 'ar' => 'وسط']);

        $this->get('/en/admin/branches')
            ->assertSee('data-name="Demo Downtown"', false)
            ->assertSee('id="delete-modal-body"', false);
    }

    /* ---------------------------------------------------------------- Access */

    /** @return array<string, array{string, string}> */
    public static function adminRoutes(): array
    {
        return [
            'index' => ['GET', 'branches'],
            'create' => ['GET', 'branches/create'],
            'store' => ['POST', 'branches'],
            'edit' => ['GET', 'branches/{id}/edit'],
            'update' => ['PUT', 'branches/{id}'],
            'toggle' => ['PATCH', 'branches/{id}/active'],
            'destroy' => ['DELETE', 'branches/{id}'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_are_redirected_to_the_localized_login(string $method, string $uri): void
    {
        foreach (['ar', 'en'] as $locale) {
            $this->refreshApplicationWithLocale($locale);
            $branch = $this->branch(['en' => 'Demo Downtown', 'ar' => 'وسط']);

            $this->call($method, "/$locale/admin/".str_replace('{id}', (string) $branch->id, $uri), $this->payload())
                ->assertRedirect("http://localhost/$locale/admin/login");

            $this->assertModelExists($branch);
            $this->assertSame(1, Branch::count());
            $this->assertTrue($branch->fresh()->is_active);
        }
    }

    #[DataProvider('adminRoutes')]
    public function test_users_of_the_web_guard_cannot_reach_the_admin(string $method, string $uri): void
    {
        $this->refreshApplicationWithLocale('en');
        $branch = $this->branch(['en' => 'Demo Downtown', 'ar' => 'وسط']);

        $this->actingAs(User::factory()->create())
            ->call($method, '/en/admin/'.str_replace('{id}', (string) $branch->id, $uri), $this->payload())
            ->assertRedirect('http://localhost/en/admin/login');

        $this->assertSame(1, Branch::count());
        $this->assertTrue($branch->fresh()->is_active);
        $this->assertSame('Demo Downtown', $branch->fresh('translations')->translate('en')->name);
    }

    public function test_guests_asking_for_json_get_401(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->postJson('/en/admin/branches', $this->payload())->assertUnauthorized();
        $this->assertSame(0, Branch::count());
    }
}
