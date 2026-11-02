<?php

namespace Tests\Feature\Localization;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every lang file exists in both locales with exactly the same keys, so no page shows a raw key
 * (a key swallowed by a stray comment, or added to one language only).
 */
class LangParityTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function enFiles(): array
    {
        // Data providers run before the application boots, so base_path() is not available yet.
        $root = dirname(__DIR__, 3);
        $files = [...glob("$root/lang/en/*.php"), ...glob("$root/Modules/*/lang/en/*.php")];

        $cases = [];
        foreach ($files as $file) {
            $cases[substr($file, strlen($root) + 1)] = [$file];
        }

        return $cases;
    }

    #[DataProvider('enFiles')]
    public function test_no_key_is_swallowed_by_a_trailing_comment(string $enFile): void
    {
        $arFile = preg_replace('#[/\\\\]en([/\\\\][^/\\\\]+\.php)$#', '/ar$1', $enFile);

        foreach ([$enFile, $arFile] as $file) {
            // `'a' => 'x', // REVIEW:        'b' => 'y',` hides 'b' in both languages, so parity cannot see it.
            $this->assertDoesNotMatchRegularExpression('/,\s*\/\/[^\n]*=>/', file_get_contents($file), $file);
        }
    }

    #[DataProvider('enFiles')]
    public function test_arabic_and_english_have_the_same_keys(string $enFile): void
    {
        $arFile = preg_replace('#[/\\\\]en([/\\\\][^/\\\\]+\.php)$#', '/ar$1', $enFile);

        $this->assertFileExists($arFile);

        $en = array_keys(Arr::dot(require $enFile));
        $ar = array_keys(Arr::dot(require $arFile));

        $this->assertSame([], array_values(array_diff($en, $ar)), 'Missing in Arabic');
        $this->assertSame([], array_values(array_diff($ar, $en)), 'Missing in English');
    }
}
