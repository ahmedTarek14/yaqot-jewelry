<?php

namespace Modules\Setting\Http\Requests;

use App\Http\Requests\Concerns\TranslatedFields;
use App\Rules\EgyptianMobile;
use App\Rules\EgyptianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Analytics\Support\GoogleAnalytics;
use Modules\Setting\Rules\SiteLink;

/**
 * Every setting is optional, so an empty settings table keeps working. A translatable
 * value filled in one language is required in the others, so no page silently shows
 * the other language (the hero slides' texts included).
 */
class SettingRequest extends FormRequest
{
    use TranslatedFields;

    /**
     * Translatable keys => maximum length.
     */
    public const TRANSLATED = [
        'about_content' => 10000,
        'footer_text' => 1000,
        'meta_description' => 300,
    ];

    /**
     * The translatable texts of each hero slide (hero_{n}_{field}) => maximum length.
     */
    public const HERO_TEXTS = [
        'title' => 255,
        'subtitle' => 500,
        'cta_label' => 100,
    ];

    /**
     * Plain https links.
     */
    public const URLS = ['facebook_url', 'instagram_url', 'tiktok_url'];

    /**
     * @return list<int>
     */
    public static function heroSlides(): array
    {
        return range(1, (int) config('setting.hero_slides'));
    }

    /**
     * Every translatable key with its maximum length, including the hero slides' texts.
     *
     * @return array<string, int>
     */
    public static function translatedKeys(): array
    {
        $keys = self::TRANSLATED;

        foreach (self::heroSlides() as $slide) {
            foreach (self::HERO_TEXTS as $field => $max) {
                $keys["hero_{$slide}_{$field}"] = $max;
            }
        }

        return $keys;
    }

    /**
     * The auth:admin middleware already restricts every admin route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The measurement id is trimmed and upper-cased, so "g-abc123" is accepted as "G-ABC123".
     */
    protected function prepareForValidation(): void
    {
        $id = $this->input('ga_measurement_id');

        if (is_string($id)) {
            $id = mb_strtoupper(trim($id));

            $this->merge(['ga_measurement_id' => $id === '' ? null : $id]);
        }
    }

    public function messages(): array
    {
        return [
            'ga_measurement_id.regex' => __('setting::setting.validation.ga_measurement_id'),
        ];
    }

    public function rules(): array
    {
        $locales = config('translatable.locales');

        $rules = [
            // Stored normalized: EgyptianPhone::normalizeMobile() / normalizePhone().
            'whatsapp_number' => ['nullable', 'string', new EgyptianMobile],
            'phone' => ['nullable', 'string', new EgyptianPhoneNumber],
            'email' => ['nullable', 'string', 'max:255', 'email:rfc'],
            'prominent_collection' => ['nullable', 'integer', Rule::exists('collections', 'id')->where('is_active', true)],
            'ga_measurement_id' => ['nullable', 'string', 'regex:'.GoogleAnalytics::PATTERN],
        ];

        foreach (self::URLS as $key) {
            $rules[$key] = ['nullable', 'string', 'max:2048', 'url:https'];
        }

        // A hero button link is an https URL or a path on this site; empty = the catalog.
        foreach (self::heroSlides() as $slide) {
            $rules["hero_{$slide}_cta_url"] = ['nullable', 'string', 'max:2048', new SiteLink];
            $rules["hero_{$slide}_enabled"] = ['nullable', 'boolean'];
        }

        foreach (self::translatedKeys() as $key => $max) {
            foreach ($locales as $locale) {
                $others = array_map(fn (string $other) => "{$key}_{$other}", array_diff($locales, [$locale]));

                $rules["{$key}_{$locale}"] = ['nullable', 'string', "max:$max", 'required_with:'.implode(',', $others)];
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        $labels = array_combine(
            array_keys(self::TRANSLATED),
            array_map(fn (string $key) => __("setting::setting.fields.$key"), array_keys(self::TRANSLATED)),
        );
        $heroUrls = [];

        foreach (self::heroSlides() as $slide) {
            foreach (array_keys(self::HERO_TEXTS) as $field) {
                $labels["hero_{$slide}_{$field}"] = __('setting::setting.hero_slide_field', [
                    'slide' => $slide,
                    'field' => __("setting::setting.fields.hero_$field"),
                ]);
            }

            $heroUrls["hero_{$slide}_cta_url"] = __('setting::setting.hero_slide_field', [
                'slide' => $slide,
                'field' => __('setting::setting.fields.hero_cta_url'),
            ]);
        }

        return [
            ...$this->translatedAttributes($labels),
            ...$heroUrls,
            'whatsapp_number' => __('setting::setting.fields.whatsapp_number'),
            'phone' => __('setting::setting.fields.phone'),
            'email' => __('setting::setting.fields.email'),
            'facebook_url' => __('setting::setting.fields.facebook_url'),
            'instagram_url' => __('setting::setting.fields.instagram_url'),
            'tiktok_url' => __('setting::setting.fields.tiktok_url'),
            'prominent_collection' => __('setting::setting.fields.prominent_collection'),
            'ga_measurement_id' => __('setting::setting.fields.ga_measurement_id'),
        ];
    }
}
