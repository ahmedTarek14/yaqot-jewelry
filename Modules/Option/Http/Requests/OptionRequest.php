<?php

namespace Modules\Option\Http\Requests;

use App\Http\Requests\Concerns\TranslatedFields;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Option\Http\Controllers\Dashboard\OptionController;

/**
 * Gold colours and stone types share these fields; the group comes from the controller
 * the route points at (it only changes the field label).
 */
class OptionRequest extends FormRequest
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
        $controller = $this->route()?->getController();
        $group = $controller instanceof OptionController ? $controller->group() : 'gold_color';

        return [
            ...$this->translatedAttributes([
                'name' => __("option::option.$group.fields.name"),
            ]),
            'is_active' => __('admin.form.is_active'),
            'sort_order' => __('admin.form.sort_order'),
        ];
    }
}
