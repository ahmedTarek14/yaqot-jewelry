<?php

namespace Tests\Unit;

use App\Support\WhatsApp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Normalisation and URL building (static, no application). The configured number
 * and the localized messages are covered in Tests\Feature\Support\WhatsAppConfiguredTest.
 */
class WhatsAppTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function acceptedForms(): array
    {
        return [
            'national 01…' => ['01012345678'],
            'plus country code +201…' => ['+201012345678'],
            'double zero 00201…' => ['00201012345678'],
            'bare country code 201…' => ['201012345678'],
            'with spaces' => ['+20 10 1234 5678'],
        ];
    }

    #[DataProvider('acceptedForms')]
    public function test_it_normalizes_every_accepted_form_to_201(string $input): void
    {
        $this->assertSame('201012345678', WhatsApp::normalize($input));
    }

    /** @return array<string, array{?string}> */
    public static function rejected(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'landline' => ['0223456789'],
            'hotline' => ['19123'],
            'too short' => ['0101234567'],
            'letters' => ['0101234567x'],
            'another country' => ['+441012345678'],
        ];
    }

    #[DataProvider('rejected')]
    public function test_it_rejects_anything_else(?string $input): void
    {
        $this->assertNull(WhatsApp::normalize($input));
        $this->assertNull(WhatsApp::link($input, 'Hello'));
    }

    public function test_it_builds_a_wa_me_link_without_a_message(): void
    {
        $this->assertSame('https://wa.me/201012345678', WhatsApp::link('01012345678'));
        $this->assertSame('https://wa.me/201012345678', WhatsApp::link('01012345678', '   '));
    }

    public function test_the_message_is_raw_url_encoded(): void
    {
        $this->assertSame(
            'https://wa.me/201012345678?text=Hello%20Yaqot%2C%20is%20%22DEMO-1%22%20available%3F%0Ahttps%3A%2F%2Fexample.com%2Fen%2Fproducts%2Fa%3Fb%3D1%26c%3D2',
            WhatsApp::link('+20 10 1234 5678', "Hello Yaqot, is \"DEMO-1\" available?\nhttps://example.com/en/products/a?b=1&c=2"),
        );
    }

    public function test_arabic_messages_are_encoded_as_utf8(): void
    {
        $this->assertSame(
            'https://wa.me/201012345678?text='.rawurlencode('مرحبًا ياقوت'),
            WhatsApp::link('01012345678', 'مرحبًا ياقوت'),
        );
        $this->assertStringContainsString('%D9%85%D8%B1%D8%AD%D8%A8', WhatsApp::link('01012345678', 'مرحب'));
    }
}
