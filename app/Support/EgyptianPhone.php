<?php

namespace App\Support;

/**
 * Egyptian phone numbers.
 *
 * Accepted input forms, with any spaces, dashes, dots or brackets, in Latin or
 * Arabic-Indic digits: national (01012345678, 0223456789), or international
 * (+20..., 0020..., 20...).
 *
 * - normalizeMobile(): mobile numbers only, in the international form WhatsApp
 *   expects: 201XXXXXXXXX. For WhatsApp numbers (App\Rules\EgyptianMobile).
 * - normalizePhone(): a landline or a mobile number in the national form shown to
 *   visitors: 0XXXXXXXXX(X), or a 5-digit hotline starting with 1 (19xxx, 16xxx),
 *   which has no area or country code. For phone fields (App\Rules\EgyptianPhoneNumber),
 *   never for WhatsApp numbers.
 */
final class EgyptianPhone
{
    /** Mobile network prefixes after the leading 0: Vodafone, Etisalat/e&, Orange, WE. */
    public const MOBILE_PREFIXES = ['10', '11', '12', '15'];

    /**
     * Landline area codes after the leading 0 => digits after the 0 in total.
     * Cairo/Giza (2) have 8-digit subscriber numbers, Alexandria (3) 7, and every
     * two-digit governorate code 7.
     */
    public const LANDLINE_LENGTHS = [
        '2' => 9, '3' => 8,
        '13' => 9, '15' => 9, '40' => 9, '45' => 9, '46' => 9, '47' => 9, '48' => 9,
        '50' => 9, '55' => 9, '57' => 9, '62' => 9, '64' => 9, '65' => 9, '66' => 9,
        '68' => 9, '69' => 9, '82' => 9, '84' => 9, '86' => 9, '88' => 9, '92' => 9,
        '93' => 9, '95' => 9, '96' => 9, '97' => 9,
    ];

    private const ARABIC_INDIC = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    private const EASTERN_ARABIC_INDIC = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    /**
     * The number as 201XXXXXXXXX, or null when it is not a valid Egyptian mobile number.
     */
    public static function normalizeMobile(?string $input): ?string
    {
        $national = self::national($input);

        return $national !== null && self::isMobileNational($national) ? '20'.$national : null;
    }

    public static function isMobile(?string $input): bool
    {
        return self::normalizeMobile($input) !== null;
    }

    /**
     * A landline or mobile number as 0XXXXXXXXX(X), a hotline as its 5 digits, or null
     * when it is none of these.
     */
    public static function normalizePhone(?string $input): ?string
    {
        $digits = self::digits($input);

        if ($digits !== null && preg_match('/^1\d{4}$/', $digits)) {
            return $digits;
        }

        $national = self::national($input);

        if ($national === null) {
            return null;
        }

        return self::isMobileNational($national) || self::isLandlineNational($national) ? '0'.$national : null;
    }

    public static function isPhone(?string $input): bool
    {
        return self::normalizePhone($input) !== null;
    }

    /**
     * The digits after the trunk 0 / country code, or null when the input is not a
     * well-formed Egyptian number at all.
     */
    private static function national(?string $input): ?string
    {
        $digits = self::digits($input);

        if ($digits === null) {
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

        // A second trunk 0 (+20 0...) is not a valid number.
        if ($national === null || $national === '' || str_starts_with($national, '0')) {
            return null;
        }

        return $national;
    }

    /**
     * The input in Latin digits without separators (a leading + or 00 kept), or null when
     * it contains anything else.
     */
    private static function digits(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = str_replace(self::ARABIC_INDIC, range(0, 9), $input);
        $digits = str_replace(self::EASTERN_ARABIC_INDIC, range(0, 9), $digits);

        // Only separators may be dropped; letters or other symbols make the input invalid.
        $digits = preg_replace('/[\s\-.()\x{200E}\x{200F}]/u', '', $digits);

        return preg_match('/^(?:\+|00)?\d+$/', $digits) ? $digits : null;
    }

    private static function isMobileNational(string $national): bool
    {
        return strlen($national) === 10 && in_array(substr($national, 0, 2), self::MOBILE_PREFIXES, true);
    }

    private static function isLandlineNational(string $national): bool
    {
        foreach ([substr($national, 0, 1), substr($national, 0, 2)] as $code) {
            if (isset(self::LANDLINE_LENGTHS[$code]) && strlen($national) === self::LANDLINE_LENGTHS[$code]) {
                return true;
            }
        }

        return false;
    }
}
