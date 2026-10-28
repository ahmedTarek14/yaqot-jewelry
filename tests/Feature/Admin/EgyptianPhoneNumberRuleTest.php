<?php

namespace Tests\Feature\Admin;

use App\Rules\EgyptianPhoneNumber;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class EgyptianPhoneNumberRuleTest extends TestCase
{
    public function test_it_accepts_landlines_mobiles_and_hotlines(): void
    {
        foreach (['0223456789', '+20 3 456 7890', '01012345678', '٠١١١٢٣٤٥٦٧٨', '19123', '16789'] as $number) {
            $this->assertTrue(Validator::make(['phone' => $number], ['phone' => [new EgyptianPhoneNumber]])->passes(), $number);
        }
    }

    public function test_it_rejects_other_values_with_a_translated_message(): void
    {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);

            $validator = Validator::make(['phone' => '1234'], ['phone' => [new EgyptianPhoneNumber]], [], ['phone' => 'Phone']);

            $this->assertTrue($validator->fails());
            $this->assertSame(
                __('admin.validation.egyptian_phone', ['attribute' => 'Phone'], $locale),
                $validator->errors()->first('phone'),
            );
        }
    }

    public function test_it_rejects_non_strings(): void
    {
        $this->assertTrue(Validator::make(['phone' => ['0223456789']], ['phone' => [new EgyptianPhoneNumber]])->fails());
        $this->assertTrue(Validator::make(['phone' => 223456789], ['phone' => [new EgyptianPhoneNumber]])->fails());
    }
}
