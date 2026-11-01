<?php

return [

    // The hero reads its title, subtitle, image and button from Settings; these are
    // the fallbacks while a setting is empty (the title falls back to the brand name).
    'hero' => [
        // REVIEW: wording to be approved by the store owner.
        'eyebrow' => 'Gold jewelry',
        // REVIEW: button label while the "hero button label" setting is empty.
        'cta' => 'Browse jewelry',
    ],

    // REVIEW: section headings and the contact invitation on the homepage.
    'sections' => [
        'categories' => 'Shop by category',
        'featured' => 'Featured pieces',
        'collections' => 'Collections',
        'brands' => 'Brands we carry',
        'latest' => 'New arrivals',
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
