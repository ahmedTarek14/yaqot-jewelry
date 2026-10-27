<?php

namespace Tests\Feature\Admin;

use App\Rules\EgyptianMobile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class EgyptianMobileRuleTest extends TestCase
{
    public function test_it_accepts_any_supported_form(): void
    {
        foreach (['01012345678', '+20 10 1234 5678', '00201512345678', '٠١١١٢٣٤٥٦٧٨'] as $number) {
            $this->assertTrue(Validator::make(['whatsapp' => $number], ['whatsapp' => [new EgyptianMobile]])->passes(), $number);
        }
    }

    public function test_it_rejects_other_values_with_a_translated_message(): void
    {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);

            $validator = Validator::make(['whatsapp' => '0223456789'], ['whatsapp' => [new EgyptianMobile]], [], ['whatsapp' => 'WhatsApp']);

            $this->assertTrue($validator->fails());
            $this->assertSame(
                __('admin.validation.egyptian_mobile', ['attribute' => 'WhatsApp'], $locale),
                $validator->errors()->first('whatsapp'),
            );
        }
    }

    public function test_it_rejects_non_strings(): void
    {
        $this->assertTrue(Validator::make(['whatsapp' => ['01012345678']], ['whatsapp' => [new EgyptianMobile]])->fails());
        $this->assertTrue(Validator::make(['whatsapp' => 1012345678], ['whatsapp' => [new EgyptianMobile]])->fails());
    }
}
