<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * "My account": the signed-in admin's own name, email and password. The current password
 * is required only when it matters: a new password, or a changed email (a login credential).
 */
class AccountRequest extends FormRequest
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
        $admin = $this->user('admin');

        $needsCurrentPassword = fn (): bool => filled($this->input('password'))
            || mb_strtolower((string) $this->input('email')) !== mb_strtolower($admin->email);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($admin)],
            'current_password' => [Rule::requiredIf($needsCurrentPassword), 'nullable', 'string', 'current_password:admin'],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('auth::admins.fields.name'),
            'email' => __('auth::admins.fields.email'),
            'current_password' => __('auth::admins.fields.current_password'),
            'password' => __('auth::admins.fields.new_password'),
        ];
    }
}
