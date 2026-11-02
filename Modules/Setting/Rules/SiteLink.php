<?php

namespace Modules\Setting\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A link the owner types for a button: an https URL, or a path on this site ("/catalog").
 * Anything else (http:, javascript:, protocol-relative "//host", backslashes, spaces) is refused.
 */
class SiteLink implements ValidationRule
{
    public static function isValid(mixed $value): bool
    {
        if (! is_string($value) || $value === '' || strlen($value) > 2048 || preg_match('/[\x00-\x20\x7f\\\\]/', $value)) {
            return false;
        }

        if (str_starts_with($value, '/')) {
            return ! str_starts_with($value, '//');
        }

        return str_starts_with(strtolower($value), 'https://')
            && filter_var($value, FILTER_VALIDATE_URL) !== false
            && parse_url($value, PHP_URL_HOST) !== null;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid($value)) {
            $fail('setting::setting.validation.site_link')->translate();
        }
    }
}
