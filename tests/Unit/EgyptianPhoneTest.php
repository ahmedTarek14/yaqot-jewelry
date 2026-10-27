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
}
