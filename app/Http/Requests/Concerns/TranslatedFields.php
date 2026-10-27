<?php

namespace App\Http\Requests\Concerns;

/**
 * Rules and labels for {field}_{locale} inputs in admin Form Requests.
 * Labels name the language, so a 422 message says which tab to fix:
 * "The Name (Arabic) field is required."
 */
trait TranslatedFields
{
    /**
     * @param  array<string, array<int, mixed>>  $fields  field => rules (applied in every locale)
     * @return array<string, array<int, mixed>>
     */
    protected function translatedRules(array $fields): array
    {
        $rules = [];

        foreach (config('translatable.locales') as $locale) {
            foreach ($fields as $field => $fieldRules) {
                $rules["{$field}_{$locale}"] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * @param  array<string, string>  $labels  field => translated label
     * @return array<string, string>
     */
    protected function translatedAttributes(array $labels): array
    {
        $attributes = [];

        foreach (config('translatable.locales') as $locale) {
            $language = __("admin.form.language_names.$locale");

            foreach ($labels as $field => $label) {
                $attributes["{$field}_{$locale}"] = "$label ($language)";
            }
        }

        return $attributes;
    }
}
