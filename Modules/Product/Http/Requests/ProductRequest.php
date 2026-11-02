<?php

namespace Modules\Product\Http\Requests;

use App\Http\Requests\Concerns\TranslatedFields;
use App\Support\ImageUploader;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Option\Models\ProductOption;
use Modules\Product\Models\Product;

class ProductRequest extends FormRequest
{
    use TranslatedFields;

    /**
     * The auth:admin middleware already restricts every admin route.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->product();

        return [
            ...$this->translatedRules([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:5000'],
            ]),
            'sku' => [
                'required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/',
                // Trashed products keep their SKU, so they count too.
                Rule::unique('products', 'sku')->ignore($product),
            ],
            'type' => ['required', 'string', Rule::in(config('catalog.types'))],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            // Compared as strings ("18"), so "18.0" or "+18" never reach the model's integer allowlist.
            'karat' => ['nullable', 'integer', Rule::in(array_map('strval', config('catalog.karats')))],
            // Jewelry only: bars and coins must send neither.
            'gold_color_id' => $this->optionRules(ProductOption::GOLD_COLOR, $product?->gold_color_id),
            'stone_type_id' => $this->optionRules(ProductOption::STONE_TYPE, $product?->stone_type_id),
            'weight_grams' => ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:99999.999'],
            'is_published' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'distinct', Rule::exists('categories', 'id')],
            'collections' => ['nullable', 'array'],
            'collections.*' => ['integer', 'distinct', Rule::exists('collections', 'id')],
            'images' => ['nullable', 'array'],
            'images.*' => ImageUploader::rules('products'),
            'images_order' => ['nullable', 'array'],
            'images_order.*' => ['string', 'regex:/^(id|new):\d+$/', 'distinct'],
            'remove_images' => ['nullable', 'array'],
            // Only this product's images; on create there is nothing to remove.
            'remove_images.*' => [
                'integer', 'distinct',
                Rule::exists('product_images', 'id')->where('product_id', $product?->id ?? 0),
            ],
        ];
    }

    /**
     * Saved images − removed ones + new uploads may not exceed the per-product limit.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $max = config('catalog.images.max_per_product');
                $savedIds = $this->product()?->images()->pluck('id')->all() ?? [];

                $removed = $this->input('remove_images', []);
                $removed = is_array($removed) ? array_map('intval', array_filter($removed, 'is_numeric')) : [];

                $new = $this->file('images', []);
                $new = is_array($new) ? count($new) : 0;

                $total = count(array_diff($savedIds, $removed)) + $new;

                if ($total > $max) {
                    $validator->errors()->add('images', __('product::product.validation.too_many_images', [
                        'max' => $max,
                        'count' => $total,
                    ]));
                }
            },
        ];
    }

    /**
     * A gold colour or stone type is a jewelry-only choice among the active options of its group;
     * the option the product already has stays valid even if it was deactivated since, so
     * re-saving an old product never fails on it.
     *
     * @return array<int, mixed>
     */
    private function optionRules(string $group, ?int $current): array
    {
        if ($this->input('type') !== Product::JEWELRY) {
            return ['prohibited'];
        }

        return [
            'nullable', 'integer',
            Rule::exists('product_options', 'id')->where('group', $group)
                ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $current ?? 0)),
        ];
    }

    public function messages(): array
    {
        return [
            'gold_color_id.prohibited' => __('product::product.validation.jewelry_only'),
            'stone_type_id.prohibited' => __('product::product.validation.jewelry_only'),
            'sku.regex' => __('product::product.validation.sku_format'),
            'images_order.*.regex' => __('product::product.validation.invalid_gallery'),
        ];
    }

    public function attributes(): array
    {
        return [
            ...$this->translatedAttributes([
                'name' => __('product::product.fields.name'),
                'description' => __('product::product.fields.description'),
            ]),
            'sku' => __('product::product.fields.sku'),
            'brand_id' => __('product::product.fields.brand'),
            'type' => __('product::product.fields.type'),
            'karat' => __('product::product.fields.karat'),
            'gold_color_id' => __('product::product.fields.gold_color'),
            'stone_type_id' => __('product::product.fields.stone_type'),
            'weight_grams' => __('product::product.fields.weight_grams'),
            'is_published' => __('product::product.fields.is_published'),
            'is_featured' => __('product::product.fields.is_featured'),
            'categories' => __('product::product.fields.categories'),
            'categories.*' => __('product::product.fields.category'),
            'collections' => __('product::product.fields.collections'),
            'collections.*' => __('product::product.fields.collection'),
            'images' => __('admin.form.gallery.label'),
            'images.*' => __('product::product.fields.new_image'),
            'images_order' => __('admin.form.gallery.label'),
            'images_order.*' => __('admin.form.gallery.label'),
            'remove_images' => __('admin.form.gallery.label'),
            'remove_images.*' => __('product::product.fields.removed_image'),
        ];
    }

    private function product(): ?Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product : null;
    }
}
