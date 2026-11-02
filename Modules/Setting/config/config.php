<?php

return [
    'name' => 'Setting',

    /*
     * Every setting the site reads. SettingService rejects keys not listed here.
     * Translatable keys store one value per locale in setting_translations.
     * Never put secrets (API keys, passwords) in settings.
     */
    'keys' => [
        'whatsapp_number' => ['translatable' => false],
        'phone' => ['translatable' => false],
        'email' => ['translatable' => false],
        'facebook_url' => ['translatable' => false],
        'instagram_url' => ['translatable' => false],
        'tiktok_url' => ['translatable' => false],
        'hero_image' => ['translatable' => false],
        'hero_cta_url' => ['translatable' => false],
        // The id of the collection shown first and large on the homepage; empty = none.
        'prominent_collection' => ['translatable' => false],
        // Optional Google Analytics 4 measurement id (G-XXXXXXXX). Empty = no tracking, no notice.
        'ga_measurement_id' => ['translatable' => false],

        'hero_title' => ['translatable' => true],
        'hero_subtitle' => ['translatable' => true],
        'hero_cta_label' => ['translatable' => true],
        'about_content' => ['translatable' => true],
        'footer_text' => ['translatable' => true],
        'meta_description' => ['translatable' => true],
    ],
];
