<?php

namespace Modules\Brand\Http\Requests;

use App\Http\Requests\Concerns\TranslatedFields;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Brands are text only: no logo until the owner confirms permission to use brand logos.
 */
class BrandRequest extends FormRequest
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
        return [
            ...$this->translatedRules([
                'name' => ['required', 'string', 'max:255'],
            ]),
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function attributes(): array
    {
        return [
            ...$this->translatedAttributes([
                'name' => __('brand::brand.fields.name'),
            ]),
            'is_active' => __('admin.form.is_active'),
            'sort_order' => __('admin.form.sort_order'),
        ];
    }
}
