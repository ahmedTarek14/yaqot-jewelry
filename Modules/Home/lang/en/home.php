<?php

return [

    // The hero slider (Phase 5b) reads each slide's title, subtitle and button from Settings;
    // these are the fallbacks while a setting is empty. The images are fixed files.
    'hero' => [
        // REVIEW: the Latin brand line above every slide title (kept the same in both languages).
        'eyebrow' => 'YAQOT JEWELRY',
        // REVIEW: button label while a slide's button label setting is empty.
        'cta' => 'Discover Our Collection',
        'label' => 'Featured jewelry',
        'slide_of' => 'Slide :current of :total',
        'show_slide' => 'Show slide :number',
        'pause' => 'Pause slideshow',
        'play' => 'Play slideshow',
        'scroll' => 'Scroll down to the collection',
        'slides' => [
            1 => [
                // REVIEW: slide 1 text.
                'title' => 'Your Story Begins in Gold',
                // REVIEW:
                'subtitle' => 'Gold creations designed to become part of your most beautiful moments.',
                // REVIEW: alt text of slide 1's image.
                'alt' => 'Gold engagement ring with a diamond',
            ],
            2 => [
                // REVIEW: slide 2 text.
                'title' => 'Details Worthy of Your Elegance',
                // REVIEW:
                'subtitle' => 'Pieces set with care to complete your look on every occasion.',
                // REVIEW: alt text of slide 2's image.
                'alt' => 'Gold necklace set with diamonds and a pear-shaped drop',
            ],
            3 => [
                // REVIEW: slide 3 text.
                'title' => 'Gold That Speaks of You',
                // REVIEW:
                'subtitle' => 'Bangles that express your taste in every moment.',
                // REVIEW: alt text of slide 3's image.
                'alt' => 'Three gold bangles set with diamonds',
            ],
        ],
    ],

    // REVIEW: section headings and the contact invitation on the homepage.
    'sections' => [
        'categories' => 'Shop by category',
        'featured' => 'Featured pieces',
        'collections' => 'Collections',
        'brands' => 'Brands we carry',
        'latest' => 'New arrivals',
        'bullion' => 'Gold bars & coins', // REVIEW:
        'about' => 'About Yaqot',
        'about_more' => 'Read more',
        'contact' => 'Ask us about any piece',
        'contact_text' => 'Send us a message or call, and we will help you with availability and details.',
    ],

    'dashboard' => [
        'welcome' => 'Welcome, :name',
        'intro' => 'Manage Yaqot products, categories and site content from here.',
        'add_product' => 'Add product',
        'stats' => [
            'heading' => 'Catalog at a glance',
            'products' => 'Products',
            'products_link' => 'View all products',
            'published' => 'Published',
            'published_link' => 'View published products',
            'drafts' => 'Drafts',
            'drafts_link' => 'View draft products',
            'active_categories' => 'Active categories',
            'active_categories_link' => 'View active categories',
        ],
        'recent' => [
            'title' => 'Recently updated products',
            'caption' => 'The most recently updated products',
            'all' => 'All products',
            'empty' => 'No products yet. Add your first piece to start the catalog.',
        ],
    ],

];
