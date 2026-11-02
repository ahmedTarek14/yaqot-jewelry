<?php

return [
    'name' => 'Analytics',

    /*
     * How long the daily product counters are kept (months). Older rows are removed by
     * `php artisan yaqot:prune-stats` or the dashboard's "Delete old counters" button.
     */
    'retention_months' => 13,

    /*
     * Case-insensitive fragments of a User-Agent that mark a visit as automated. Such visits
     * (and requests with no User-Agent at all) are never counted.
     */
    'bot_tokens' => [
        'bot', 'crawl', 'spider', 'slurp', 'bingpreview', 'facebookexternalhit', 'facebot',
        'whatsapp', 'telegram', 'skypeuripreview', 'embedly', 'quora link', 'pinterest',
        'headless', 'phantomjs', 'lighthouse', 'chrome-lighthouse', 'pagespeed', 'gtmetrix',
        'curl', 'wget', 'python-requests', 'python-urllib', 'aiohttp', 'httpx', 'go-http-client',
        'java/', 'okhttp', 'libwww', 'httpclient', 'axios', 'node-fetch', 'postmanruntime',
        'monitor', 'uptime', 'pingdom', 'preview', 'validator', 'scrapy', 'fetch',
    ],
];
