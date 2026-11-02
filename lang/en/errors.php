<?php

// The error pages (resources/views/errors) show both languages at once; see App\View\Components\ErrorPage.
return [

    // REVIEW: all the wording of the error pages.
    'home' => 'Back to the home page',

    'codes' => [
        '404' => [
            'title' => 'Page not found',
            'message' => 'The page you are looking for does not exist or has moved.',
        ],
        '419' => [
            'title' => 'Page expired',
            'message' => 'This page was open for too long. Please go back, reload it and try again.',
        ],
        '429' => [
            'title' => 'Too many requests',
            'message' => 'Please wait a moment and try again.',
        ],
        '500' => [
            'title' => 'Something went wrong',
            'message' => 'We could not complete your request. Please try again in a little while.',
        ],
        '503' => [
            'title' => 'We will be right back',
            'message' => 'The website is being updated. Please check back in a few minutes.',
        ],
    ],

];
