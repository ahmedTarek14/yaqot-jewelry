<?php

return [

    // The cookie notice is shown on public pages only, and only when a Google Analytics id is set in Settings.
    'consent' => [
        // REVIEW: accessible name of the cookie notice.
        'label' => 'Cookie notice',
        // REVIEW: heading of the cookie notice.
        'title' => 'Cookies and statistics',
        // REVIEW: text of the cookie notice.
        'text' => 'With your permission we use Google Analytics to count visits and learn which pages are useful. It sets cookies on your device only if you accept.',
        // REVIEW: link to the privacy policy inside the notice.
        'more' => 'Read the privacy policy',
        // REVIEW: button label.
        'accept' => 'Accept',
        // REVIEW: button label.
        'decline' => 'Decline',
    ],

    'insights' => [
        'title' => 'Insights',
        'range_label' => 'Time range',
        // REVIEW: "7 يوم" / "30 يوم" is colloquial in Arabic; the number agreement differs for 7 and 30.
        'last_days' => 'Last :days days',
        'intro' => 'Anonymous daily totals for each product: how often its page was opened and how often its WhatsApp button was pressed. No visitor data is stored.',
        'most_viewed' => 'Most viewed',
        'most_asked' => 'Most asked about on WhatsApp',
        'unit_views' => 'views',
        'unit_clicks' => 'WhatsApp clicks',
        'empty' => 'No data for this period yet.',
        'chart_title' => 'Daily totals, last 30 days',
        'chart_desc' => 'Line chart of page views and WhatsApp clicks per day over the last 30 days, for all products together. The vertical axis runs from 0 to :max.',
        'chart_empty' => 'No activity in the last 30 days.',
        'legend' => 'Chart legend',
        'series_views' => 'Page views (solid line)',
        'series_clicks' => 'WhatsApp clicks (dashed line)',
        'show_table' => 'Show the numbers as a table',
        'date' => 'Date',
        'retention' => 'Daily counters are kept for :months months and older ones are removed.',
        'prune' => [
            'button' => 'Delete old counters',
            'title' => 'Delete old counters?',
            'body' => 'Counters older than :months months will be deleted. Newer ones are not affected. This cannot be undone.',
            'confirm' => 'Delete',
            'done' => ':count old counter rows deleted.',
        ],
    ],

];
