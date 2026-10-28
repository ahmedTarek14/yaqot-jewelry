<?php

return [

    'title' => 'Products',
    'singular' => 'Product',
    'create' => 'Add product',
    'edit' => 'Edit product',
    'search_placeholder' => 'Search by name or SKU…',
    'empty' => 'No products yet. Add the first piece to start building the catalogue.',
    'table_caption' => 'Products, with their SKU, publishing status and featured flag',

    'sections' => [
        'details' => 'Details',
        'classification' => 'Categories and collections',
        'images' => 'Images',
        'publishing' => 'Publishing',
    ],

    'fields' => [
        'name' => 'Name',
        'description' => 'Description',
        'description_help' => 'Optional. Plain text; blank lines start a new paragraph.',
        'sku' => 'SKU',
        'sku_help' => 'Your internal code for the piece. Letters, digits, dots, dashes and underscores only.',
        'brand' => 'Brand',
        'no_brand' => 'No brand (house piece)',
        'karat' => 'Karat',
        'gold_color' => 'Gold colour',
        'stone_type' => 'Stones',
        'not_specified' => 'Not specified',
        'weight_grams' => 'Weight (grams)',
        'weight_help' => 'Optional. Up to three decimal places, e.g. 12.375.',
        'categories' => 'Categories',
        'category' => 'Category',
        'collections' => 'Collections',
        'collection' => 'Collection',
        'new_image' => 'New image :position',
        'removed_image' => 'Removed image',
        'is_published' => 'Published',
        'is_published_help' => 'Published products appear on the public site once they have at least one image.',
        'is_featured' => 'Featured',
        'is_featured_help' => 'Featured products are highlighted on the homepage.',
        'view_on_site_help' => 'Opens the product page in a new tab.',
    ],

    'images_help' => 'The first image is the primary one, shown on product cards and in search results.',

    'karats' => [
        18 => '18K',
        21 => '21K',
        24 => '24K',
    ],

    'gold_colors' => [
        'yellow' => 'Yellow gold',
        'white' => 'White gold',
        'rose' => 'Rose gold',
        'multi' => 'Multi-tone',
    ],

    'stone_types' => [
        'none' => 'No stones',
        'diamond' => 'Diamond',
        'zircon' => 'Zircon',
        'gemstone' => 'Gemstones',
        'pearl' => 'Pearl',
    ],

    'featured' => [
        'on' => 'Featured',
        'off' => 'Not featured',
    ],

    'columns' => [
        'sku' => 'SKU',
        'featured' => 'Featured',
    ],

    'filters' => [
        'status' => 'Status',
        'category' => 'Category',
        'collection' => 'Collection',
        'brand' => 'Brand',
        'no_brand' => 'No brand',
        'karat' => 'Karat',
        'featured' => 'Featured',
        'featured_yes' => 'Featured',
        'featured_no' => 'Not featured',
        'trashed' => 'Trash',
        'trashed_only' => 'Trash only',
        'trashed_with' => 'Including trash',
    ],

    'messages' => [
        'trashed' => 'Moved to the trash. You can restore it from the trash filter.',
        'trash_confirm' => 'Move “:name” to the trash? It disappears from the site and can be restored later from the trash filter.',
        'force_confirm' => 'Delete “:name” permanently? This permanently deletes the product and its images and cannot be undone.',
        'deleted_permanently' => 'The product and its images were deleted permanently.',
    ],

    'validation' => [
        'sku_format' => 'The SKU may only contain English letters, digits, dots (.), dashes (-) and underscores (_).',
        'too_many_images' => 'A product can have up to :max images; this would make :count. Remove some images first.',
        'invalid_gallery' => 'The image order could not be read. Reload the page and try again.',
    ],

];
