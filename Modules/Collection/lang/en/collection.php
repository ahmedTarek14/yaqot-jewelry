<?php

return [

    'title' => 'Collections',
    'singular' => 'Collection',
    'create' => 'Add collection',
    'edit' => 'Edit collection',
    'search_placeholder' => 'Search collections by name…',
    'empty' => 'No collections yet. Add the first one to group pieces by theme or occasion.', // REVIEW:
    'table_caption' => 'Collections, with their product counts, status and homepage visibility',

    'fields' => [
        'name' => 'Name',
        'description' => 'Description',
        'description_help' => 'Optional. Plain text; blank lines start a new paragraph.',
        'show_on_homepage' => 'Show on the homepage',
        'show_on_homepage_help' => 'Featured on the homepage while the collection is also active.', // REVIEW:
    ],

    'homepage' => [
        'label' => 'Homepage',
        'on' => 'Shown',
        'off' => 'Hidden',
    ],

    'image_help' => 'Shown on the collection tile and page header.', // REVIEW:

];
