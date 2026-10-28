<?php

namespace Modules\Collection\Http\Requests;

use App\Http\Requests\Concerns\TranslatedFields;
use App\Support\ImageUploader;
use Illuminate\Foundation\Http\FormRequest;

class CollectionRequest extends FormRequest
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
                'description' => ['nullable', 'string', 'max:5000'],
            ]),
            'image' => ['nullable', ...ImageUploader::rules('collections')],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'show_on_homepage' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function attributes(): array
    {
        return [
            ...$this->translatedAttributes([
                'name' => __('collection::collection.fields.name'),
                'description' => __('collection::collection.fields.description'),
            ]),
            'image' => __('admin.form.image.label'),
            'remove_image' => __('admin.form.image.remove'),
            'is_active' => __('admin.form.is_active'),
            'show_on_homepage' => __('collection::collection.fields.show_on_homepage'),
            'sort_order' => __('admin.form.sort_order'),
        ];
    }
}
