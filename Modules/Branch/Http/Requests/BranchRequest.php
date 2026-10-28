<?php

namespace Modules\Branch\Http\Requests;

use App\Http\Requests\Concerns\TranslatedFields;
use App\Rules\EgyptianMobile;
use App\Rules\EgyptianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
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
                'address' => ['required', 'string', 'max:500'],
                'working_hours' => ['nullable', 'string', 'max:500'],
            ]),
            // Stored normalized: EgyptianPhone::normalizePhone() / normalizeMobile().
            'phone' => ['required', 'string', new EgyptianPhoneNumber],
            'whatsapp' => ['nullable', 'string', new EgyptianMobile],
            'map_url' => ['nullable', 'string', 'max:2048', 'url:https'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function attributes(): array
    {
        return [
            ...$this->translatedAttributes([
                'name' => __('branch::branch.fields.name'),
                'address' => __('branch::branch.fields.address'),
                'working_hours' => __('branch::branch.fields.working_hours'),
            ]),
            'phone' => __('branch::branch.fields.phone'),
            'whatsapp' => __('branch::branch.fields.whatsapp'),
            'map_url' => __('branch::branch.fields.map_url'),
            'is_active' => __('admin.form.is_active'),
            'sort_order' => __('admin.form.sort_order'),
        ];
    }
}
