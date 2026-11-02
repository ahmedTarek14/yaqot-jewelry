<?php

/*
 * Homepage hero slides (Phase 5b): hero_{n}_title|subtitle|cta_label are translatable,
 * hero_{n}_cta_url and hero_{n}_enabled are not. The images are fixed files, not settings.
 */
$heroSlides = 3;
$heroKeys = [];

foreach (range(1, $heroSlides) as $slide) {
    foreach (['title', 'subtitle', 'cta_label'] as $field) {
        $heroKeys["hero_{$slide}_{$field}"] = ['translatable' => true];
    }

    $heroKeys["hero_{$slide}_cta_url"] = ['translatable' => false];
    $heroKeys["hero_{$slide}_enabled"] = ['translatable' => false];
}

return [
    'name' => 'Setting',

    'hero_slides' => $heroSlides,

    /*
     * Every setting the site reads. SettingService rejects keys not listed here.
     * Translatable keys store one value per locale in setting_translations.
     * Never put secrets (API keys, passwords) in settings.
     *
     * The old hero_image setting is no longer registered (the slide images are fixed files);
     * a value saved earlier stays in the table, unread, and its file stays on disk.
     */
    'keys' => [
        'whatsapp_number' => ['translatable' => false],
        'phone' => ['translatable' => false],
        'email' => ['translatable' => false],
        'facebook_url' => ['translatable' => false],
        'instagram_url' => ['translatable' => false],
        'tiktok_url' => ['translatable' => false],
        // The id of the collection shown first and large on the homepage; empty = none.
        'prominent_collection' => ['translatable' => false],
        // Optional Google Analytics 4 measurement id (G-XXXXXXXX). Empty = no tracking, no notice.
        'ga_measurement_id' => ['translatable' => false],

        'about_content' => ['translatable' => true],
        'footer_text' => ['translatable' => true],
        'meta_description' => ['translatable' => true],

        ...$heroKeys,
    ],
];
