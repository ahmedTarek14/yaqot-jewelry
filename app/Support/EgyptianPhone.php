<?php

namespace App\Support;

/**
 * Egyptian mobile numbers in the international form WhatsApp expects: 201XXXXXXXXX
 * (country code 20, then a 10-digit mobile number starting with 10, 11, 12 or 15).
 *
 * Accepted input forms, with any spaces, dashes, dots or brackets, in Latin or
 * Arabic-Indic digits:
 *   01012345678, +201012345678, 00201012345678, 201012345678
 *
 * Used by the Settings form (validation + storage) and the public WhatsApp CTA.
 */
final class EgyptianPhone
{
    /** Mobile network prefixes after the leading 0: Vodafone, Etisalat/e&, Orange, WE. */
    public const MOBILE_PREFIXES = ['10', '11', '12', '15'];

    private const ARABIC_INDIC = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    private const EASTERN_ARABIC_INDIC = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    /**
     * The number as 201XXXXXXXXX, or null when it is not a valid Egyptian mobile number.
     */
    public static function normalizeMobile(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = str_replace(self::ARABIC_INDIC, range(0, 9), $input);
        $digits = str_replace(self::EASTERN_ARABIC_INDIC, range(0, 9), $digits);

        // Only separators may be dropped; letters or other symbols make the input invalid.
        $digits = preg_replace('/[\s\-.()\x{200E}\x{200F}]/u', '', $digits);

        if (! preg_match('/^(?:\+|00)?\d+$/', $digits)) {
            return null;
        }

        // "+" always introduces the country code: +20..., never +0...
        if (str_starts_with($digits, '+') && ! str_starts_with($digits, '+20')) {
            return null;
        }

        $digits = ltrim($digits, '+');

        $national = match (true) {
            str_starts_with($digits, '0020') => substr($digits, 4),
            str_starts_with($digits, '20') => substr($digits, 2),
            str_starts_with($digits, '0') => substr($digits, 1),
            default => null,
        };

        if ($national === null || ! preg_match('/^\d{10}$/', $national)) {
            return null;
        }

        if (! in_array(substr($national, 0, 2), self::MOBILE_PREFIXES, true)) {
            return null;
        }

        return '20'.$national;
    }

    public static function isMobile(?string $input): bool
    {
        return self::normalizeMobile($input) !== null;
    }
}
