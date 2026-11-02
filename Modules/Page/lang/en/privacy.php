<?php

return [

    // REVIEW: every sentence of the privacy policy. It describes only what this website does
    // today; the owner must confirm it (and add legal details if needed) before launch.
    'title' => 'Privacy policy',
    'lead' => 'This page explains, in plain words, how this website handles information when you visit it.',
    'sections' => [
        'no_accounts' => [
            'title' => 'No accounts and no forms',
            'text' => 'You do not need an account to use this website, and it has no forms that ask for your name, phone number, email address or home address. It does not take orders or payments.',
        ],
        'search' => [
            'title' => 'Search and filters',
            'text' => 'When you search or filter the jewelry, what you choose is sent to this website only to show you matching pieces, and the website does not save it. Your search words do appear in the page address, which the web server may record in its logs.',
        ],
        'cookies' => [
            'title' => 'Cookies',
            'text' => 'This website uses only the small cookies its software needs to work and to keep it secure, such as a session cookie. We do not use cookies for advertising or to track you.',
        ],
        'third_party' => [
            'title' => 'No outside analytics or services',
            // REVIEW: reworded from "no analytics": the site now keeps its own anonymous counters (see below).
            'text' => 'This website does not use analytics or advertising tools from other companies. Its scripts, fonts and images are served from this website itself, not from other companies’ servers.',
        ],
        'counters' => [
            // REVIEW: title of the in-house counters section.
            'title' => 'Anonymous product counters',
            // REVIEW: describes the in-house counters (always on); confirm the 13 months.
            'text' => 'To see which pieces interest visitors, this website keeps simple daily totals for each product: how many times its page was opened and how many times its WhatsApp button was pressed. Only these totals and the date are stored. No IP address, device details, cookie or other personal data is stored, and visits by search engines and other automated programs are not counted. The totals are deleted after 13 months.',
        ],
        'contact_links' => [
            'title' => 'WhatsApp, phone and email links',
            'text' => 'Links to WhatsApp, phone numbers and email addresses only open the app on your own device. A message you send or a call you make is handled by that service or your phone provider under its own rules, and this website does not collect it.',
        ],
        'external_links' => [
            'title' => 'Links to other sites',
            'text' => 'Links to our social media pages, to maps and to WhatsApp open other sites or apps, which have their own privacy practices.',
        ],
        'logs' => [
            'title' => 'Server logs',
            'text' => 'Like most websites, the web server that hosts this site may keep technical logs, such as the time of a visit, the page requested and the device’s IP address. What is recorded depends on the hosting service.',
        ],
    ],

    // Shown instead of the plain "cookies" and "third_party" sections, plus a Google Analytics
    // section, only while a Google Analytics ID is set in Settings.
    'sections_ga' => [
        'cookies' => [
            'title' => 'Cookies',
            // REVIEW: cookies text while Google Analytics is on.
            'text' => 'This website uses the small cookies its software needs to work and to keep it secure, such as a session cookie. If you accept statistics, Google Analytics also sets its own cookies on your device (see “Google Analytics” below). This website does not use advertising cookies.',
        ],
        'third_party' => [
            'title' => 'Outside services',
            // REVIEW: outside services text while Google Analytics is on.
            'text' => 'Apart from Google Analytics, which loads only if you accept it, this website does not use analytics or advertising tools from other companies. Its scripts, fonts and images are served from this website itself.',
        ],
        'analytics' => [
            'title' => 'Google Analytics',
            // REVIEW: Google Analytics description (processor, cookies, purpose, consent, withdrawal).
            'text' => 'If you accept, this website uses Google Analytics, a service of Google, to count visits and learn which pages are useful, so that we can improve the website. Google acts as our processor: it receives technical information from your browser, such as your IP address, and the pages you view, and it sets cookies whose names start with _ga. We do not use this for advertising. Nothing is loaded from Google and no analytics cookie is set until you choose “Accept”. Your choice is remembered in a cookie named yq_consent for 180 days. You can change it at any time with the “Cookie settings” button below; if you decline afterwards, tracking stops. Cookies Google has already set stay on your device until you delete them in your browser settings (look for cookies starting with _ga).',
        ],
    ],
    'cookie_settings' => [
        // REVIEW: cookie settings block (button re-opens the notice).
        'title' => 'Cookie settings',
        'text' => 'You can change your choice about Google Analytics at any time.',
        'button' => 'Cookie settings',
    ],

    'questions_title' => 'Questions',
    'questions_text' => 'If you have a question about this page, write to us at',

];
