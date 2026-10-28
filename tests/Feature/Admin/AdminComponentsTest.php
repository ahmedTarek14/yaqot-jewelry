<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Blade;
use Modules\Category\Models\Category;
use Tests\TestCase;

/**
 * The Phase 3b building blocks in components/admin, rendered on their own.
 */
class AdminComponentsTest extends TestCase
{
    public function test_select_marks_the_current_value_and_offers_an_empty_choice_only_with_a_placeholder(): void
    {
        $html = Blade::render('<x-admin.select name="karat" label="Karat" :options="[18 => \'18K\', 21 => \'21K\']" :value="21" placeholder="None" required />');

        $this->assertStringContainsString('<select class="form-select" id="field-karat" name="karat"', $html);
        $this->assertStringContainsString('<option value="">None</option>', $html);
        $this->assertMatchesRegularExpression('/<option value="21"\s+selected>21K<\/option>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="18"\s+selected>/', $html);
        $this->assertStringContainsString('aria-required="true"', $html);

        $withoutPlaceholder = Blade::render('<x-admin.select name="brand_id" label="Brand" :options="[1 => \'A\']" />');
        $this->assertStringNotContainsString('<option value="">', $withoutPlaceholder);
    }

    public function test_select_treats_null_as_no_value_rather_than_zero(): void
    {
        $html = Blade::render('<x-admin.select name="x" label="X" :options="[0 => \'Zero\']" :value="null" placeholder="None" />');

        $this->assertDoesNotMatchRegularExpression('/<option value="0"\s+selected>/', $html);
    }

    public function test_multiselect_checks_the_selected_options_and_counts_them(): void
    {
        $html = Blade::render('<x-admin.multiselect name="categories" label="Categories" :options="[3 => \'Rings\', 5 => \'Bracelets\', 8 => \'<b>Sets</b>\']" :selected="[\'5\', 8, 99]" />');

        $this->assertStringContainsString('data-field-errors="categories"', $html);
        $this->assertStringContainsString('name="categories[]" value="3"', $html);
        $this->assertMatchesRegularExpression('/value="5"\s+id="field-categories-5"\s+checked/', $html);
        $this->assertMatchesRegularExpression('/value="8"\s+id="field-categories-8"\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="3"\s+id="field-categories-3"\s+checked/', $html);
        $this->assertStringContainsString(__('admin.form.multiselect.selected', ['count' => 2]), $html);
        $this->assertStringContainsString('&lt;b&gt;Sets&lt;/b&gt;', $html);
        // The search box appears only once admin.js is running.
        $this->assertMatchesRegularExpression('/<input type="search"[^>]*data-multiselect-search hidden/', $html);
    }

    public function test_multiselect_without_options_says_so(): void
    {
        $html = Blade::render('<x-admin.multiselect name="collections" label="Collections" :options="[]" empty="No collections yet." />');

        $this->assertStringContainsString('No collections yet.', $html);
        $this->assertStringNotContainsString('type="checkbox"', $html);
    }

    public function test_gallery_lists_saved_images_in_order_with_tokens_and_disabled_removal_inputs(): void
    {
        $images = collect([
            (object) ['id' => 12, 'url' => '/storage/products/1/a.webp'],
            (object) ['id' => 7, 'url' => '/storage/products/1/b.webp'],
        ]);

        $html = Blade::render('<x-admin.image-gallery type="products" :images="$images" />', ['images' => $images]);

        $this->assertStringContainsString('data-field-errors="images images_order remove_images"', $html);
        $this->assertLessThan(strpos($html, 'value="id:7"'), strpos($html, 'value="id:12"'));
        $this->assertMatchesRegularExpression('/name="remove_images\[\]" value="12" disabled/', $html);
        $this->assertStringContainsString('name="images[]" multiple', $html);
        $this->assertStringContainsString('src="/storage/products/1/a.webp"', $html);
        $this->assertStringContainsString('800', $html); // the products minimum in the hint
        $this->assertStringContainsString(__('admin.form.gallery.help', ['max' => config('catalog.images.max_per_product')]), $html);
        $this->assertStringContainsString('<template data-gallery-template>', $html);
        $this->assertMatchesRegularExpression('/data-gallery-empty\s+hidden/', $html);
    }

    public function test_an_empty_gallery_shows_its_empty_message(): void
    {
        $html = Blade::render('<x-admin.image-gallery type="products" :images="[]" :max="5" />');

        $this->assertDoesNotMatchRegularExpression('/data-gallery-empty\s+hidden/', $html);
        $this->assertStringContainsString(__('admin.form.gallery.help', ['max' => 5]), $html);
    }

    public function test_translatable_fields_take_their_values_from_an_array_without_a_model(): void
    {
        $html = Blade::render('<x-admin.translatable-fields :model="null" :values="$values" :fields="[\'hero_title\' => [\'label\' => \'Title\']]" />', [
            'values' => ['hero_title_ar' => 'عنوان', 'hero_title_en' => 'Title <en>'],
        ]);

        $this->assertStringContainsString('name="hero_title_ar"', $html);
        $this->assertStringContainsString('value="عنوان"', $html);
        $this->assertStringContainsString('value="Title &lt;en&gt;"', $html);
    }

    public function test_restore_button_is_a_patch_form_named_after_the_item(): void
    {
        $html = Blade::render('<x-admin.restore-button url="/en/admin/products/5/restore" name="Ring" />');

        $this->assertStringContainsString('action="/en/admin/products/5/restore"', $html);
        $this->assertStringContainsString('name="_method" value="PATCH"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString(__('admin.actions.restore_item', ['name' => 'Ring']), $html);
    }

    public function test_name_cell_without_a_url_is_plain_text(): void
    {
        $category = Category::factory()->make(['en' => ['name' => 'Demo Rings'], 'ar' => ['name' => 'خواتم']]);

        $linked = Blade::render('<x-admin.name-cell :model="$m" url="/en/admin/categories/1/edit" />', ['m' => $category]);
        $plain = Blade::render('<x-admin.name-cell :model="$m" />', ['m' => $category]);

        $this->assertStringContainsString('<a href="/en/admin/categories/1/edit" class="yq-table__title">Demo Rings</a>', $linked);
        $this->assertStringContainsString('<span class="yq-table__title">Demo Rings</span>', $plain);
        $this->assertStringNotContainsString('<a ', $plain);
        $this->assertStringContainsString('خواتم', $plain);
    }

    public function test_badge_and_stat_card_render_text(): void
    {
        $this->assertStringContainsString('yq-badge--off', Blade::render('<x-admin.badge :on="false" label="Draft" />'));
        $this->assertStringContainsString('>Draft</span>', Blade::render('<x-admin.badge :on="false" label="Draft" />'));

        $card = Blade::render('<x-admin.stat-card label="Published" :value="12" icon="gem" url="/en/admin/products" link-label="See products" />');
        $this->assertStringContainsString('Published', $card);
        $this->assertStringContainsString('>12</p>', $card);
        $this->assertStringContainsString('href="/en/admin/products"', $card);
        $this->assertStringContainsString('See products', $card);
    }
}
