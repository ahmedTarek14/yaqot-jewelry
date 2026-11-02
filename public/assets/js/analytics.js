/*
 * Yaqot: cookie notice and Google Analytics loader (public pages).
 *
 * Included only when a Google Analytics id is set in Settings. Nothing is requested from Google
 * until the visitor accepts. The choice is kept in a first-party cookie (yq_consent, 180 days).
 * Without JavaScript the notice stays hidden and Google Analytics is never loaded.
 */
(function () {
    'use strict';

    var notice = document.querySelector('[data-analytics-consent]');

    if (!notice) {
        return;
    }

    var measurementId = notice.getAttribute('data-measurement-id') || '';

    // The id is validated on the server; checked again because it becomes part of a script URL.
    if (!/^G-[A-Z0-9]{4,12}$/.test(measurementId)) {
        return;
    }

    var COOKIE_NAME = 'yq_consent';
    var MAX_AGE = 180 * 24 * 60 * 60;
    var loaded = false;
    var opener = null;

    function readChoice() {
        var match = document.cookie.match(/(?:^|;\s*)yq_consent=(granted|denied)(?:;|$)/);

        return match ? match[1] : null;
    }

    function writeChoice(value) {
        var cookie = COOKIE_NAME + '=' + value + '; max-age=' + MAX_AGE + '; path=/; SameSite=Lax';

        if (window.location.protocol === 'https:') {
            cookie += '; Secure';
        }

        document.cookie = cookie;
    }

    function loadAnalytics() {
        // Re-enable after an earlier "decline" in this page view.
        window['ga-disable-' + measurementId] = false;

        if (loaded) {
            return;
        }

        loaded = true;
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () {
            window.dataLayer.push(arguments);
        };
        window.gtag('js', new Date());
        window.gtag('config', measurementId, { anonymize_ip: true });

        var script = document.createElement('script');
        script.async = true;
        script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(measurementId);
        document.head.appendChild(script);
    }

    function stopAnalytics() {
        // Stops any further hits from this page. Cookies Google already set stay until the visitor deletes them.
        window['ga-disable-' + measurementId] = true;
    }

    function showNotice(focus) {
        notice.hidden = false;

        if (focus) {
            var first = notice.querySelector('[data-consent-choice]');

            if (first) {
                first.focus();
            }
        }
    }

    function choose(value) {
        writeChoice(value);
        notice.hidden = true;

        if (value === 'granted') {
            loadAnalytics();
        } else {
            stopAnalytics();
        }

        if (opener) {
            opener.focus();
            opener = null;
        }
    }

    notice.addEventListener('click', function (event) {
        var button = event.target.closest('[data-consent-choice]');

        if (button) {
            choose(button.getAttribute('data-consent-choice'));
        }
    });

    // "Cookie settings" buttons (the privacy page) re-open the notice.
    var openers = document.querySelectorAll('[data-consent-open]');

    Array.prototype.forEach.call(openers, function (button) {
        button.hidden = false;
        button.addEventListener('click', function () {
            opener = button;
            showNotice(true);
        });
    });

    var choice = readChoice();

    if (choice === 'granted') {
        loadAnalytics();
    } else if (choice === null) {
        showNotice(false);
    }
})();
