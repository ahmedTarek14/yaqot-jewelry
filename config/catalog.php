<?php

/*
| Allowlists for product attributes. Changing a list here needs no code edits;
| values already stored keep working until they are edited.
*/

return [

    'karats' => [18, 21, 24],

    'gold_colors' => ['yellow', 'white', 'rose', 'multi'],

    'stone_types' => ['none', 'diamond', 'zircon', 'gemstone', 'pearl'],

    /*
    | Admin image uploads (App\Support\ImageUploader). Every upload is re-encoded
    | to WebP, which also drops EXIF metadata such as GPS positions. The pixel
    | limits keep GD's memory use bounded (a 6000×6000 image needs ~144 MB).
    | min_pixels is per upload type (also the storage folder): product photos
    | are shown large in the gallery, so they need more detail than tiles.
    | 'settings' is the homepage hero image.
    */
    'images' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kilobytes' => 5120,
        'min_pixels' => [
            'categories' => 200,
            'collections' => 200,
            'products' => 800,
            'settings' => 800,
        ],
        'max_pixels' => 6000,
        'max_edge' => 1600,
        'quality' => 82,
        'max_per_product' => 12,
    ],

    'admin' => [
        'per_page' => 20,
    ],

    /*
    | Public site. The catalog shows per_page products a page (divisible by the
    | 2, 3 and 4 grid columns). Homepage sections show at most these many items.
    | Three collections fit the layout: the one chosen in Settings
    | ("prominent_collection") large, with two beside it.
    | search_max_length: longer search terms are cut to this many characters.
    | related: pieces shown under a product (same category, newest first).
    */
    'front' => [
        'per_page' => 24,
        'search_max_length' => 100,
        'related' => 4,
        'home' => [
            'categories' => 8,
            'featured' => 8,
            'collections' => 3,
            'brands' => 12,
            'latest' => 8,
        ],
    ],

];
