<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Create (POST) and edit (PUT) an admin user. On create the password is required and the
 * admin starts active; on edit the password is optional (it sets a new one) and is_active
 * is validated here but applied by the controller only after the safeguards.
 */
class AdminRequest extends FormRequest
{
    /**
     * The auth:admin middleware already restricts every admin route.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }

        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('admins', 'email')->ignore($this->route('admin')),
            ],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'confirmed', Password::defaults()],
            ...($creating ? [] : ['is_active' => ['required', 'boolean']]),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('auth::admins.fields.name'),
            'email' => __('auth::admins.fields.email'),
            'password' => __('auth::admins.fields.password'),
            'is_active' => __('admin.form.is_active'),
        ];
    }
}
