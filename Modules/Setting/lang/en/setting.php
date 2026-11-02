<?php

return [

    'title' => 'Settings',
    'intro' => 'Every field is optional. A text filled in one language must also be filled in the other, so no page shows the wrong language.',

    // REVIEW: the section descriptions say where each value appears; confirm once the public pages are built.
    'sections' => [
        'contact' => [
            'title' => 'Contact',
            'description' => 'Shown in the footer and on the contact page.',
        ],
        'social' => [
            'title' => 'Social media',
            'description' => 'Full https links to the shop’s pages. Leave a field empty to hide its icon.',
        ],
        'hero' => [
            // REVIEW: says what the owner can and cannot change in the hero.
            'description' => 'The large slider at the top of the homepage. The pictures are fixed; you edit each slide’s text and button, or switch a slide off.',
            'slide' => 'Hero slide :number: :subject',
            'subjects' => [
                1 => 'ring',
                2 => 'necklace',
                3 => 'bangles',
            ],
        ],
        'homepage' => [
            'title' => 'Homepage',
            'description' => 'How the homepage sections are arranged.',
        ],
        'about' => [
            'title' => 'About',
            'description' => 'The text of the About page.',
        ],
        'footer' => [
            'title' => 'Footer',
            'description' => 'A short line shown at the bottom of every page.',
        ],
        'seo' => [
            'title' => 'Search engines',
            'description' => 'The default description search engines show for the site.',
        ],
        'analytics' => [
            'title' => 'Google Analytics',
            // REVIEW: wording of the analytics section.
            'description' => 'Optional visitor statistics from Google.',
        ],
    ],

    // Attribute label in validation errors, e.g. "Slide 2 title (Arabic)".
    'hero_slide_field' => 'Slide :slide :field',

    'validation' => [
        // REVIEW: error shown for a hero button link that is neither https nor a path starting with "/".
        'site_link' => 'Enter a link starting with https://, or a page of this site starting with / (for example /catalog).',
        // REVIEW: error shown for a malformed Google Analytics measurement ID.
        'ga_measurement_id' => 'Enter a valid measurement ID such as G-ABC123XYZ9, or leave the field empty.',
    ],

    'fields' => [
        'whatsapp_number' => 'WhatsApp number',
        'whatsapp_number_help' => 'An Egyptian mobile number. It is saved in the international form WhatsApp uses.',
        'phone' => 'Phone',
        'phone_help' => 'An Egyptian landline, mobile or 5-digit hotline (19XXX). It is saved in the national form, e.g. 0XXXXXXXXXX.',
        'email' => 'Email',
        'facebook_url' => 'Facebook link',
        'instagram_url' => 'Instagram link',
        'tiktok_url' => 'TikTok link',
        'url_help' => 'A full link starting with https://',
        'hero_title' => 'Title',
        'hero_subtitle' => 'Subtitle',
        'hero_cta_label' => 'Button label',
        'hero_cta_url' => 'Button link',
        // REVIEW: help texts of the hero slide fields.
        'hero_cta_help' => 'An https link, or a page of this site such as /catalog (the visitor’s language is added). Empty = the catalog.',
        'hero_enabled' => 'Show this slide',
        'hero_enabled_help' => 'Empty texts use the standard wording. With one slide on, the slider has no controls; with all off, slide 1 shows with its standard text.',
        'prominent_collection' => 'Featured collection',
        'prominent_collection_none' => 'None',
        'prominent_collection_help' => 'Shown first and large among the homepage collections, when it is set to appear on the homepage and has published pieces.',
        'ga_measurement_id' => 'Measurement ID',
        // REVIEW: help text of the Google Analytics field.
        'ga_measurement_id_help' => 'Looks like G-XXXXXXXXXX. Leave empty to turn tracking off: nothing is loaded from Google and visitors see no cookie notice. When filled, visitors see a cookie notice and Google Analytics loads only after they accept.',
        'about_content' => 'About text',
        'about_content_help' => 'Plain text; blank lines start a new paragraph.',
        'footer_text' => 'Footer text',
        'meta_description' => 'Meta description',
        'meta_description_help' => 'Up to 300 characters; about 150 show in search results.',
    ],

];
