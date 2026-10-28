<?php

namespace App\Rules;

use App\Support\EgyptianPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An Egyptian landline, mobile or 5-digit hotline (19xxx) in any form EgyptianPhone accepts.
 * Store EgyptianPhone::normalizePhone($value) after validation.
 * For WhatsApp numbers use EgyptianMobile instead.
 */
class EgyptianPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! EgyptianPhone::isPhone($value)) {
            $fail('admin.validation.egyptian_phone')->translate();
        }
    }
}
