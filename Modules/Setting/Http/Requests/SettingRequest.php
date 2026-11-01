<?php

namespace Modules\Setting\Http\Requests;

use App\Http\Requests\Concerns\TranslatedFields;
use App\Rules\EgyptianMobile;
use App\Rules\EgyptianPhoneNumber;
use App\Support\ImageUploader;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Every setting is optional, so an empty settings table keeps working. A translatable
 * value filled in one language is required in the others, so no page silently shows
 * the other language; the hero button needs both its label and its link.
 */
class SettingRequest extends FormRequest
{
    use TranslatedFields;

    /**
     * Translatable keys => maximum length.
     */
    public const TRANSLATED = [
        'hero_title' => 255,
        'hero_subtitle' => 500,
        'hero_cta_label' => 100,
        'about_content' => 10000,
        'footer_text' => 1000,
        'meta_description' => 300,
    ];

    /**
     * Plain https links.
     */
    public const URLS = ['facebook_url', 'instagram_url', 'tiktok_url', 'hero_cta_url'];

    /**
     * The auth:admin middleware already restricts every admin route.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $locales = config('translatable.locales');

        $rules = [
            // Stored normalized: EgyptianPhone::normalizeMobile() / normalizePhone().
            'whatsapp_number' => ['nullable', 'string', new EgyptianMobile],
            'phone' => ['nullable', 'string', new EgyptianPhoneNumber],
            'email' => ['nullable', 'string', 'max:255', 'email:rfc'],
            'hero_image' => ['nullable', ...ImageUploader::rules('settings')],
            'remove_hero_image' => ['nullable', 'boolean'],
            'prominent_collection' => ['nullable', 'integer', Rule::exists('collections', 'id')->where('is_active', true)],
        ];

        foreach (self::URLS as $key) {
            $rules[$key] = ['nullable', 'string', 'max:2048', 'url:https'];
        }

        $rules['hero_cta_url'][] = 'required_with:'.implode(',', array_map(fn (string $locale) => "hero_cta_label_$locale", $locales));

        foreach (self::TRANSLATED as $key => $max) {
            foreach ($locales as $locale) {
                $others = array_map(fn (string $other) => "{$key}_{$other}", array_diff($locales, [$locale]));

                if ($key === 'hero_cta_label') {
                    $others[] = 'hero_cta_url';
                }

                $rules["{$key}_{$locale}"] = ['nullable', 'string', "max:$max", 'required_with:'.implode(',', $others)];
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            ...$this->translatedAttributes(array_combine(
                array_keys(self::TRANSLATED),
                array_map(fn (string $key) => __("setting::setting.fields.$key"), array_keys(self::TRANSLATED)),
            )),
            'whatsapp_number' => __('setting::setting.fields.whatsapp_number'),
            'phone' => __('setting::setting.fields.phone'),
            'email' => __('setting::setting.fields.email'),
            'facebook_url' => __('setting::setting.fields.facebook_url'),
            'instagram_url' => __('setting::setting.fields.instagram_url'),
            'tiktok_url' => __('setting::setting.fields.tiktok_url'),
            'hero_cta_url' => __('setting::setting.fields.hero_cta_url'),
            'hero_image' => __('setting::setting.fields.hero_image'),
            'prominent_collection' => __('setting::setting.fields.prominent_collection'),
            'remove_hero_image' => __('admin.form.image.remove'),
        ];
    }
}
