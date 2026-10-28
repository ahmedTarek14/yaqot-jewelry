<?php

namespace Tests\Unit;

use App\Support\EgyptianPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EgyptianPhoneTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function validNumbers(): array
    {
        return [
            'local 010' => ['01012345678', '201012345678'],
            'local 011' => ['01112345678', '201112345678'],
            'local 012' => ['01212345678', '201212345678'],
            'local 015' => ['01512345678', '201512345678'],
            'plus country code' => ['+201012345678', '201012345678'],
            'double zero country code' => ['00201012345678', '201012345678'],
            'bare country code' => ['201012345678', '201012345678'],
            'spaces' => ['010 1234 5678', '201012345678'],
            'dashes and brackets' => ['(+20) 10-1234-5678', '201012345678'],
            'dots' => ['010.1234.5678', '201012345678'],
            'surrounding whitespace' => ["  01012345678\t", '201012345678'],
            'arabic-indic digits' => ['٠١٠١٢٣٤٥٦٧٨', '201012345678'],
            'eastern arabic-indic digits' => ['۰۱۰۱۲۳۴۵۶۷۸', '201012345678'],
            'mixed digits with plus' => ['+٢٠ ١٠ 1234 5678', '201012345678'],
            'bidi marks from copy-paste' => ["\u{200E}+20 10 1234 5678\u{200E}", '201012345678'],
        ];
    }

    /** @return array<string, array{?string}> */
    public static function invalidNumbers(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'only spaces' => ['   '],
            'landline (Cairo)' => ['0223456789'],
            'landline with country code' => ['+20223456789'],
            'unknown mobile prefix 013' => ['01312345678'],
            'unknown mobile prefix 019' => ['01912345678'],
            'too short' => ['0101234567'],
            'too long' => ['010123456789'],
            'too long with country code' => ['2010123456789'],
            'no leading zero' => ['1012345678'],
            'other country code' => ['+971501234567'],
            'plus without country code' => ['+01012345678'],
            'letters' => ['0101234567a'],
            'words' => ['call me'],
            'plus in the middle' => ['010+12345678'],
            'double plus' => ['++201012345678'],
            'extension' => ['01012345678 ext 2'],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_it_normalizes_egyptian_mobile_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, EgyptianPhone::normalizeMobile($input));
        $this->assertTrue(EgyptianPhone::isMobile($input));
    }

    #[DataProvider('invalidNumbers')]
    public function test_it_rejects_anything_else(?string $input): void
    {
        $this->assertNull(EgyptianPhone::normalizeMobile($input));
        $this->assertFalse(EgyptianPhone::isMobile($input));
    }

    public function test_normalizing_is_idempotent(): void
    {
        $once = EgyptianPhone::normalizeMobile('01012345678');

        $this->assertSame($once, EgyptianPhone::normalizeMobile($once));
    }

    /* ------------------------------------------------- Landline or mobile */

    /** @return array<string, array{string, string}> */
    public static function validPhones(): array
    {
        return [
            'mobile' => ['01012345678', '01012345678'],
            'mobile with country code' => ['+20 10 1234 5678', '01012345678'],
            'Cairo landline' => ['0223456789', '0223456789'],
            'Cairo landline with country code' => ['+20 2 2345 6789', '0223456789'],
            'Cairo landline with 0020' => ['0020223456789', '0223456789'],
            'Alexandria landline' => ['034567890', '034567890'],
            'Mansoura landline' => ['050 2345678', '0502345678'],
            'Aswan landline' => ['(097) 234-5678', '0972345678'],
            '10th of Ramadan landline (015 + 7 digits)' => ['0151234567', '0151234567'],
            'arabic-indic Cairo landline' => ['٠٢٢٣٤٥٦٧٨٩', '0223456789'],
            'hotline 19xxx' => ['19123', '19123'],
            'hotline 16xxx with a space' => ['16 789', '16789'],
            'arabic-indic hotline' => ['١٩١٢٣', '19123'],
        ];
    }

    /** @return array<string, array{?string}> */
    public static function invalidPhones(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'Cairo too short' => ['022345678'],
            'Cairo too long' => ['02234567890'],
            'Alexandria too long' => ['0345678901'],
            'unknown area code 014' => ['0142345678'],
            'unknown area code 099' => ['0992345678'],
            'no leading zero' => ['223456789'],
            'second trunk zero after country code' => ['+200223456789'],
            'hotline not starting with 1' => ['29123'],
            'hotline too short' => ['1912'],
            'hotline too long' => ['191234'],
            'hotline with a country code' => ['+2019123'],
            'hotline with plus' => ['+19123'],
            'other country code' => ['+971501234567'],
            'letters' => ['02234567a9'],
        ];
    }

    #[DataProvider('validPhones')]
    public function test_it_normalizes_landlines_and_mobiles_to_the_national_form(string $input, string $expected): void
    {
        $this->assertSame($expected, EgyptianPhone::normalizePhone($input));
        $this->assertTrue(EgyptianPhone::isPhone($input));
    }

    #[DataProvider('invalidPhones')]
    public function test_it_rejects_other_phone_values(?string $input): void
    {
        $this->assertNull(EgyptianPhone::normalizePhone($input));
        $this->assertFalse(EgyptianPhone::isPhone($input));
    }

    public function test_a_landline_or_hotline_is_never_a_whatsapp_number(): void
    {
        foreach (['0223456789', '19123', '16789'] as $number) {
            $this->assertTrue(EgyptianPhone::isPhone($number), $number);
            $this->assertFalse(EgyptianPhone::isMobile($number), $number);
            $this->assertNull(EgyptianPhone::normalizeMobile($number), $number);
        }
    }

    public function test_normalizing_a_phone_is_idempotent(): void
    {
        foreach (['+20 2 2345 6789', '01012345678'] as $input) {
            $once = EgyptianPhone::normalizePhone($input);

            $this->assertSame($once, EgyptianPhone::normalizePhone($once));
        }
    }
}
