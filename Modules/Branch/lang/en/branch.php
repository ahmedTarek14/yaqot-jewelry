<?php

return [

    'title' => 'Branches',
    'singular' => 'Branch',
    'create' => 'Add branch',
    'edit' => 'Edit branch',
    'search_placeholder' => 'Search branches by name or phone…',
    'empty' => 'No branches yet. Add the first one so visitors know where to find you.',
    'table_caption' => 'Branches, with their phone numbers and status',

    'columns' => [
        'phone' => 'Phone',
    ],

    'sections' => [
        'contact' => 'Contact and location',
    ],

    'fields' => [
        'name' => 'Name',
        'address' => 'Address',
        'working_hours' => 'Working hours',
        // REVIEW: generic example format only, not real opening hours.
        'working_hours_help' => 'Optional. Plain text, for example “Saturday–Thursday: 00:00–00:00”.',
        'phone' => 'Phone',
        'phone_help' => 'An Egyptian landline, mobile or 5-digit hotline (19XXX). It is saved in the national form, e.g. 0XXXXXXXXXX.',
        'whatsapp' => 'WhatsApp number',
        'whatsapp_help' => 'Optional. An Egyptian mobile number; it is saved in the international form WhatsApp uses.',
        'map_url' => 'Map link',
        'map_url_help' => 'Optional. Google Maps share link (https).',
    ],

];
