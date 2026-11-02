<?php

namespace Tests\Feature\Option;

use App\Exceptions\ModelInUseException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Modules\Auth\Models\Admin;
use Modules\Option\Models\ProductOption;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Gold colours and stone types admin: one controller, request and set of views serve both
 * groups, so every behaviour runs against each of them. The data migration seeds the legacy
 * options in every database; signIn() clears them so the tests control the rows.
 */
class OptionAdminTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string}> group, URL segment */
    public static function optionGroups(): array
    {
        return [
            'gold colours' => [ProductOption::GOLD_COLOR, 'gold-colors'],
            'stone types' => [ProductOption::STONE_TYPE, 'stone-types'],
        ];
    }

    /** @return array<string, array{string, string, string}> group, URL segment, locale */
    public static function groupsAndLocales(): array
    {
        $cases = [];
        foreach (self::optionGroups() as $label => [$group, $segment]) {
            foreach (['ar', 'en'] as $locale) {
                $cases["$label, $locale"] = [$group, $segment, $locale];
            }
        }

        return $cases;
    }

    private function signIn(string $locale = 'en'): static
    {
        $this->refreshApplicationWithLocale($locale);
        ProductOption::query()->get()->each->delete();

        return $this->actingAs(Admin::factory()->create(), 'admin');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name_ar' => 'خيار تجريبي',
            'name_en' => 'Demo Option',
            'is_active' => '1',
            'sort_order' => '3',
        ], $overrides);
    }

    /**
     * @param  array<string, string>  $names
     * @param  array<string, mixed>  $attributes
     */
    private function option(string $group, array $names, array $attributes = []): ProductOption
    {
        return ProductOption::factory()->create([
            ...$attributes,
            'group' => $group,
            'en' => ['name' => $names['en']],
            'ar' => ['name' => $names['ar']],
        ]);
    }

    private function product(string $group, ProductOption $option, array $attributes = []): Product
    {
        $factory = Product::factory();
        $factory = $group === ProductOption::GOLD_COLOR ? $factory->withGoldColor($option) : $factory->withStoneType($option);

        return $factory->create($attributes);
    }

    private function route(string $segment, string $name, mixed $parameter = null): string
    {
        return route("admin.$segment.$name", $parameter);
    }

    /* ----------------------------------------------------------------- Index */

    #[DataProvider('groupsAndLocales')]
    public function test_the_index_lists_options_with_status_text_and_both_names(string $group, string $segment, string $locale): void
    {
        $this->signIn($locale);
        $active = $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا تجريبي']);
        $inactive = $this->option($group, ['en' => 'Demo Beta', 'ar' => 'بيتا تجريبي'], ['is_active' => false]);
        $this->product($group, $active);
        $this->product($group, $active);

        $this->get("/$locale/admin/$segment")
            ->assertOk()
            ->assertSee('<html lang="'.$locale.'"', false)
            ->assertSee(__("option::option.$group.title", [], $locale))
            ->assertSee('Demo Alpha')
            ->assertSee('ألفا تجريبي')
            ->assertSee('Demo Beta')
            ->assertSee(__('admin.status.active', [], $locale))
            ->assertSee(__('admin.status.inactive', [], $locale))
            ->assertSee('data-toggle-url="'.$this->route($segment, 'toggle-active', $inactive->id).'"', false)
            ->assertSee('data-url="'.$this->route($segment, 'destroy', $active->id).'"', false)
            ->assertSee('href="'.$this->route($segment, 'edit', $active->id).'"', false)
            ->assertSee('<td class="d-none d-md-table-cell">2</td>', false);
    }

    #[DataProvider('optionGroups')]
    public function test_the_index_shows_only_its_own_group(string $group, string $segment): void
    {
        $this->signIn();
        $other = $group === ProductOption::GOLD_COLOR ? ProductOption::STONE_TYPE : ProductOption::GOLD_COLOR;
        $this->option($group, ['en' => 'Demo Mine', 'ar' => 'خاصتي']);
        $this->option($other, ['en' => 'Demo Theirs', 'ar' => 'خاصتهم']);

        $this->get("/en/admin/$segment")->assertOk()->assertSee('Demo Mine')->assertDontSee('Demo Theirs');
    }

    #[DataProvider('optionGroups')]
    public function test_the_products_count_uses_the_groups_column_and_leaves_out_trashed_products(string $group, string $segment): void
    {
        $this->signIn();
        $option = $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا']);
        $this->product($group, $option);
        $this->product($group, $option);
        $this->product($group, $option)->delete();

        $this->get("/en/admin/$segment")->assertOk()->assertSee('<td class="d-none d-md-table-cell">2</td>', false);
    }

    #[DataProvider('optionGroups')]
    public function test_the_index_searches_names_in_either_language(string $group, string $segment): void
    {
        $this->signIn();
        $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا تجريبي']);
        $this->option($group, ['en' => 'Demo Beta', 'ar' => 'بيتا تجريبي']);

        $this->get("/en/admin/$segment?q=alph")->assertOk()->assertSee('Demo Alpha')->assertDontSee('Demo Beta');
        $this->get("/en/admin/$segment?q=".urlencode('بيتا'))->assertOk()->assertSee('Demo Beta')->assertDontSee('Demo Alpha');
        $this->get("/en/admin/$segment?q=_")->assertOk()->assertSee(__('admin.listing.empty_filtered'));
    }

    #[DataProvider('optionGroups')]
    public function test_the_index_filters_by_status(string $group, string $segment): void
    {
        $this->signIn();
        $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا']);
        $this->option($group, ['en' => 'Demo Beta', 'ar' => 'بيتا'], ['is_active' => false]);

        $this->get("/en/admin/$segment?status=inactive")->assertOk()->assertSee('Demo Beta')->assertDontSee('Demo Alpha');
        $this->get("/en/admin/$segment?status=active")->assertOk()->assertSee('Demo Alpha')->assertDontSee('Demo Beta');
        $this->get("/en/admin/$segment?status=deleted")->assertOk()->assertSee('Demo Alpha')->assertSee('Demo Beta');
    }

    #[DataProvider('optionGroups')]
    public function test_the_index_sorts_by_allowlisted_columns_only(string $group, string $segment): void
    {
        $this->signIn();
        $this->option($group, ['en' => 'Demo Bravo', 'ar' => 'ب'], ['sort_order' => 1]);
        $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'أ'], ['sort_order' => 2]);
        $charlie = $this->option($group, ['en' => 'Demo Charlie', 'ar' => 'ج'], ['sort_order' => 3]);
        $this->product($group, $charlie);

        $this->get("/en/admin/$segment")->assertSeeInOrder(['Demo Bravo', 'Demo Alpha', 'Demo Charlie']);
        $this->get("/en/admin/$segment?sort=name")->assertSeeInOrder(['Demo Alpha', 'Demo Bravo', 'Demo Charlie']);
        $this->get("/en/admin/$segment?sort=name&dir=desc")->assertSeeInOrder(['Demo Charlie', 'Demo Bravo', 'Demo Alpha']);
        $this->get("/en/admin/$segment?sort=products_count&dir=desc")->assertSeeInOrder(['Demo Charlie', 'Demo Bravo']);
        $this->get("/en/admin/$segment?sort=slug&dir=desc")->assertOk()->assertSeeInOrder(['Demo Bravo', 'Demo Alpha', 'Demo Charlie']);
        $this->get("/en/admin/$segment?sort=id);drop table product_options;--")->assertOk();
    }

    #[DataProvider('optionGroups')]
    public function test_the_index_paginates_and_a_page_past_the_end_redirects(string $group, string $segment): void
    {
        $this->signIn();
        config(['catalog.admin.per_page' => 2]);
        foreach (range(1, 5) as $i) {
            $this->option($group, ['en' => "Demo Item $i", 'ar' => "عنصر $i"], ['sort_order' => $i]);
        }

        $this->get("/en/admin/$segment?status=active")
            ->assertOk()
            ->assertSee('Demo Item 1')
            ->assertDontSee('Demo Item 3')
            ->assertSee('status=active&amp;page=2', false);

        $this->get("/en/admin/$segment?status=active&page=9")
            ->assertRedirect("http://localhost/en/admin/$segment?status=active&page=3");
    }

    #[DataProvider('groupsAndLocales')]
    public function test_the_index_has_empty_states(string $group, string $segment, string $locale): void
    {
        $this->signIn($locale);

        $this->get("/$locale/admin/$segment")
            ->assertOk()
            ->assertSee(__("option::option.$group.empty", [], $locale))
            ->assertSee('href="'.$this->route($segment, 'create').'"', false);

        $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا']);

        $this->get("/$locale/admin/$segment?q=nothing-matches")
            ->assertOk()
            ->assertSee(__('admin.listing.empty_filtered', [], $locale))
            ->assertDontSee(__("option::option.$group.empty", [], $locale));
    }

    /* ------------------------------------------------------- Create and edit */

    #[DataProvider('groupsAndLocales')]
    public function test_the_create_page_has_the_form_with_locale_tabs(string $group, string $segment, string $locale): void
    {
        $this->signIn($locale);

        $response = $this->get("/$locale/admin/$segment/create");

        $this->assertMatchesRegularExpression('/name="is_active"\s+value="1" checked/', $response->getContent());

        $response->assertOk()
            ->assertSee(__("option::option.$group.create", [], $locale))
            ->assertSee('class="ajax-form"', false)
            ->assertSee('action="'.$this->route($segment, 'store').'"', false)
            ->assertSeeInOrder(['data-bs-target="#translations-ar"', 'data-bs-target="#translations-en"'], false)
            ->assertSee('name="name_ar"', false)
            ->assertSee('name="name_en"', false)
            ->assertSee('name="sort_order"', false)
            ->assertDontSee('name="slug"', false)
            ->assertDontSee('name="_method" value="PUT"', false);
    }

    #[DataProvider('groupsAndLocales')]
    public function test_the_edit_page_is_filled_with_both_translations(string $group, string $segment, string $locale): void
    {
        $this->signIn($locale);
        $option = $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا تجريبي'], ['is_active' => false, 'sort_order' => 7]);

        $response = $this->get("/$locale/admin/$segment/{$option->id}/edit");

        $this->assertMatchesRegularExpression('/name="is_active"\s+value="1"\s+aria-describedby/', $response->getContent());

        $response->assertOk()
            ->assertSee(__("option::option.$group.edit", [], $locale))
            ->assertSee('action="'.$this->route($segment, 'update', $option->id).'"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('value="Demo Alpha"', false)
            ->assertSee('value="ألفا تجريبي"', false)
            ->assertSee('value="7"', false);
    }

    #[DataProvider('optionGroups')]
    public function test_an_unknown_option_returns_404(string $group, string $segment): void
    {
        $this->signIn();

        $this->get("/en/admin/$segment/9999/edit")->assertNotFound();
        $this->putJson("/en/admin/$segment/9999", $this->payload())->assertNotFound();
        $this->patchJson("/en/admin/$segment/9999/active")->assertNotFound();
        $this->delete("/en/admin/$segment/9999")->assertNotFound();
    }

    #[DataProvider('optionGroups')]
    public function test_an_id_from_the_other_group_returns_404_and_changes_nothing(string $group, string $segment): void
    {
        $this->signIn();
        $other = $group === ProductOption::GOLD_COLOR ? ProductOption::STONE_TYPE : ProductOption::GOLD_COLOR;
        $foreign = $this->option($other, ['en' => 'Demo Theirs', 'ar' => 'خاصتهم']);

        $this->get("/en/admin/$segment/{$foreign->id}/edit")->assertNotFound();
        $this->putJson("/en/admin/$segment/{$foreign->id}", $this->payload(['name_en' => 'Hijacked']))->assertNotFound();
        $this->patchJson("/en/admin/$segment/{$foreign->id}/active")->assertNotFound();
        $this->delete("/en/admin/$segment/{$foreign->id}")->assertNotFound();

        $foreign = $foreign->fresh('translations');
        $this->assertTrue($foreign->is_active);
        $this->assertSame('Demo Theirs', $foreign->translate('en')->name);
    }

    /* ----------------------------------------------------------------- Store */

    #[DataProvider('groupsAndLocales')]
    public function test_store_creates_the_option_in_its_group_with_a_slug_from_the_english_name(string $group, string $segment, string $locale): void
    {
        $this->signIn($locale);

        $this->postJson("/$locale/admin/$segment", $this->payload(['name_en' => 'Demo Sunset Tone']))
            ->assertOk()
            ->assertExactJson([
                'message' => __('admin.messages.saved', [], $locale),
                'redirect' => "http://localhost/$locale/admin/$segment",
            ]);

        $option = ProductOption::with('translations')->sole();
        $this->assertSame($group, $option->group);
        $this->assertSame('Demo Sunset Tone', $option->translate('en')->name);
        $this->assertSame('خيار تجريبي', $option->translate('ar')->name);
        $this->assertSame('demo-sunset-tone', $option->slug);
        $this->assertTrue($option->is_active);
        $this->assertSame(3, $option->sort_order);
    }

    #[DataProvider('optionGroups')]
    public function test_store_saves_an_inactive_option(string $group, string $segment): void
    {
        $this->signIn();

        $this->postJson("/en/admin/$segment", $this->payload(['is_active' => '0', 'sort_order' => '0']))->assertOk();

        $option = ProductOption::sole();
        $this->assertFalse($option->is_active);
        $this->assertSame(0, $option->sort_order);
    }

    /* ------------------------------------------------------------ Validation */

    #[DataProvider('groupsAndLocales')]
    public function test_validation_returns_422_json_with_every_error(string $group, string $segment, string $locale): void
    {
        $this->signIn($locale);

        $response = $this->postJson("/$locale/admin/$segment", [
            'name_ar' => '',
            'name_en' => str_repeat('a', 256),
            'is_active' => 'maybe',
            'sort_order' => '65536',
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors(['name_ar', 'name_en', 'is_active', 'sort_order']);

        $nameAr = __("option::option.$group.fields.name", [], $locale).' ('.__('admin.form.language_names.ar', [], $locale).')';
        $this->assertSame(
            __('validation.required', ['attribute' => $nameAr], $locale),
            $response->json('errors.name_ar.0'),
        );

        $this->assertSame(0, ProductOption::count());
    }

    #[DataProvider('optionGroups')]
    public function test_both_names_are_required_and_sort_order_has_bounds(string $group, string $segment): void
    {
        $this->signIn();

        $this->postJson("/en/admin/$segment", ['is_active' => '1', 'sort_order' => '0'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name_ar', 'name_en'])
            ->assertJsonMissingValidationErrors(['is_active', 'sort_order']);

        $this->postJson("/en/admin/$segment", $this->payload(['sort_order' => '-1']))
            ->assertUnprocessable()->assertJsonValidationErrors(['sort_order']);
        $this->postJson("/en/admin/$segment", $this->payload(['sort_order' => '65536']))
            ->assertUnprocessable()->assertJsonValidationErrors(['sort_order']);
        $this->postJson("/en/admin/$segment", $this->payload(['sort_order' => '65535']))->assertOk();

        $this->assertSame(1, ProductOption::count());
    }

    /* ---------------------------------------------------------------- Update */

    #[DataProvider('optionGroups')]
    public function test_update_changes_fields_and_translations_but_never_the_slug_or_group(string $group, string $segment): void
    {
        $this->signIn();
        $option = $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا']);
        $slug = $option->slug;

        $this->putJson("/en/admin/$segment/{$option->id}", $this->payload([
            'name_en' => 'Demo Alpha Renamed',
            'name_ar' => 'ألفا معدل',
            'is_active' => '0',
            'sort_order' => '9',
        ]))->assertOk()->assertJsonPath('redirect', "http://localhost/en/admin/$segment");

        $option = $option->fresh('translations');
        $this->assertSame('Demo Alpha Renamed', $option->translate('en')->name);
        $this->assertSame('ألفا معدل', $option->translate('ar')->name);
        $this->assertSame($slug, $option->slug);
        $this->assertSame('demo-alpha', $option->slug);
        $this->assertSame($group, $option->group);
        $this->assertFalse($option->is_active);
        $this->assertSame(9, $option->sort_order);
        $this->assertSame(2, $option->translations()->count());
    }

    #[DataProvider('optionGroups')]
    public function test_update_validation_returns_422_and_changes_nothing(string $group, string $segment): void
    {
        $this->signIn();
        $option = $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا']);

        $this->putJson("/en/admin/$segment/{$option->id}", $this->payload(['name_en' => '   ']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name_en']);

        $this->assertSame('Demo Alpha', $option->fresh('translations')->translate('en')->name);
    }

    /* ---------------------------------------------------------------- Toggle */

    #[DataProvider('groupsAndLocales')]
    public function test_the_active_switch_flips_the_flag_and_returns_the_text_status(string $group, string $segment, string $locale): void
    {
        $this->signIn($locale);
        $option = $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا']);

        $this->patchJson("/$locale/admin/$segment/{$option->id}/active")
            ->assertOk()
            ->assertJson([
                'value' => false,
                'label' => __('admin.status.inactive', [], $locale),
            ]);
        $this->assertFalse($option->fresh()->is_active);

        $this->patchJson("/$locale/admin/$segment/{$option->id}/active")
            ->assertOk()
            ->assertJson(['value' => true, 'label' => __('admin.status.active', [], $locale)]);
        $this->assertTrue($option->fresh()->is_active);
    }

    /* ---------------------------------------------------------------- Delete */

    #[DataProvider('optionGroups')]
    public function test_delete_removes_an_unused_option_and_its_translations(string $group, string $segment): void
    {
        $this->signIn();
        $option = $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا']);

        $this->from("/en/admin/$segment?status=active")
            ->delete("/en/admin/$segment/{$option->id}")
            ->assertRedirect("/en/admin/$segment?status=active")
            ->assertSessionHas('success', __('admin.messages.deleted'));

        $this->assertModelMissing($option);
        $this->assertSame(0, $option->translations()->count());
    }

    #[DataProvider('optionGroups')]
    public function test_a_product_using_a_different_option_does_not_block_the_delete(string $group, string $segment): void
    {
        $this->signIn();
        $option = $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا']);
        // A product that uses a different option of the same group does not block this one.
        $this->product($group, $this->option($group, ['en' => 'Demo Beta', 'ar' => 'بيتا']));

        $this->delete("/en/admin/$segment/{$option->id}")->assertSessionHas('success');

        $this->assertModelMissing($option);
    }

    /** @return array<string, array{string}> */
    public static function usages(): array
    {
        return [
            'a visible product' => ['visible'],
            'a draft product' => ['draft'],
            'a trashed product' => ['trashed'],
        ];
    }

    #[DataProvider('usages')]
    public function test_a_used_option_cannot_be_deleted_only_deactivated(string $usage): void
    {
        foreach (self::optionGroups() as [$group, $segment]) {
            $this->signIn();
            $option = $this->option($group, ['en' => "Demo $usage", 'ar' => 'خيار']);
            $product = $this->product($group, $option, match ($usage) {
                'draft' => ['is_published' => false],
                default => ['is_published' => true],
            });
            $trashed = 0;
            if ($usage === 'trashed') {
                $product->delete();
                $trashed = 1;
            }

            $message = ModelInUseException::describe(1, $trashed);

            $this->from("/en/admin/$segment")
                ->delete("/en/admin/$segment/{$option->id}")
                ->assertRedirect("/en/admin/$segment")
                ->assertSessionHas('error', $message)
                ->assertSessionMissing('success');

            $this->assertModelExists($option);
            $this->assertSame(1, ProductOption::query()->whereKey($option->id)->count());

            $this->deleteJson("/en/admin/$segment/{$option->id}")
                ->assertStatus(409)
                ->assertExactJson(['message' => $message]);

            $this->assertModelExists($option);

            // Deactivating is the way out.
            $this->patchJson("/en/admin/$segment/{$option->id}/active")->assertOk()->assertJson(['value' => false]);
        }
    }

    #[DataProvider('optionGroups')]
    public function test_a_refusal_counts_trashed_products_and_is_not_reported_as_an_error(string $group, string $segment): void
    {
        $this->signIn('ar');
        Exceptions::fake();
        $option = $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا']);
        $this->product($group, $option);
        $this->product($group, $option)->delete();

        $message = ModelInUseException::describe(2, 1);
        $this->assertNotSame(ModelInUseException::describe(2, 0), $message);

        $this->from("/ar/admin/$segment")
            ->delete("/ar/admin/$segment/{$option->id}")
            ->assertRedirect("/ar/admin/$segment")
            ->assertSessionHas('error', $message);

        $this->assertModelExists($option);
        Exceptions::assertNothingReported();
    }

    /* ---------------------------------------------------------------- Access */

    /** @return array<string, array{string, string, string}> */
    public static function adminRoutes(): array
    {
        $cases = [];
        foreach (['gold-colors', 'stone-types'] as $segment) {
            foreach ([
                'index' => ['GET', ''],
                'create' => ['GET', '/create'],
                'store' => ['POST', ''],
                'edit' => ['GET', '/{id}/edit'],
                'update' => ['PUT', '/{id}'],
                'toggle' => ['PATCH', '/{id}/active'],
                'destroy' => ['DELETE', '/{id}'],
            ] as $name => [$method, $uri]) {
                $cases["$segment $name"] = [$method, $segment, $uri];
            }
        }

        return $cases;
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_are_redirected_to_the_localized_login(string $method, string $segment, string $uri): void
    {
        $group = $segment === 'gold-colors' ? ProductOption::GOLD_COLOR : ProductOption::STONE_TYPE;

        foreach (['ar', 'en'] as $locale) {
            $this->refreshApplicationWithLocale($locale);
            $option = $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا']);

            $this->call($method, "/$locale/admin/$segment".str_replace('{id}', (string) $option->id, $uri), $this->payload())
                ->assertRedirect("http://localhost/$locale/admin/login");

            $this->assertModelExists($option);
            $this->assertTrue($option->fresh()->is_active);
        }
    }

    #[DataProvider('adminRoutes')]
    public function test_users_of_the_web_guard_cannot_reach_the_admin(string $method, string $segment, string $uri): void
    {
        $group = $segment === 'gold-colors' ? ProductOption::GOLD_COLOR : ProductOption::STONE_TYPE;
        $this->refreshApplicationWithLocale('en');
        $option = $this->option($group, ['en' => 'Demo Alpha', 'ar' => 'ألفا']);

        $this->actingAs(User::factory()->create())
            ->call($method, "/en/admin/$segment".str_replace('{id}', (string) $option->id, $uri), $this->payload())
            ->assertRedirect('http://localhost/en/admin/login');

        $this->assertSame('Demo Alpha', $option->fresh('translations')->translate('en')->name);
    }

    public function test_guests_asking_for_json_get_401(): void
    {
        $this->refreshApplicationWithLocale('en');
        $before = ProductOption::count();

        $this->postJson('/en/admin/gold-colors', $this->payload())->assertUnauthorized();
        $this->postJson('/en/admin/stone-types', $this->payload())->assertUnauthorized();
        $this->assertSame($before, ProductOption::count());
    }

    #[DataProvider('groupsAndLocales')]
    public function test_the_admin_navigation_lists_both_entries(string $group, string $segment, string $locale): void
    {
        $this->signIn($locale);

        $response = $this->get("/$locale/admin/$segment");

        $response->assertOk()
            ->assertSee('href="'.$this->route('gold-colors', 'index').'"', false)
            ->assertSee('href="'.$this->route('stone-types', 'index').'"', false)
            ->assertSee(__('admin.nav.gold_colors', [], $locale))
            ->assertSee(__('admin.nav.stone_types', [], $locale));
    }
}
