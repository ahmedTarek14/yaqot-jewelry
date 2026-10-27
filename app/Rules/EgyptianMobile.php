<?php

namespace App\Rules;

use App\Support\EgyptianPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An Egyptian mobile number in any form EgyptianPhone accepts.
 * Store EgyptianPhone::normalizeMobile($value) after validation.
 */
class EgyptianMobile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! EgyptianPhone::isMobile($value)) {
            $fail('admin.validation.egyptian_mobile')->translate();
        }
    }
}
