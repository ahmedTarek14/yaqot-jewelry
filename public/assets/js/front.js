/*
 * Yaqot public site behaviour (jQuery). Motion patterns: see docs/CONVENTIONS.md "Motion"
 * and front.css §7. This file only toggles classes; all motion lives in CSS
 * (transform and opacity only) and is switched off by prefers-reduced-motion.
 */
(function ($) {
    'use strict';

    // Tells the layout's failsafe (master.blade.php) that motion JS is running,
    // so it keeps html.js and the reveal styles.
    window.yqMotionReady = true;

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* 1. Scroll reveal: [data-reveal] gets .is-revealed once it enters the
          viewport. Inside [data-reveal-group], items are numbered for the stagger. */
    function initReveal() {
        var $items = $('[data-reveal]');

        $('[data-reveal-group]').each(function () {
            $(this).find('[data-reveal]').each(function (index) {
                this.style.setProperty('--yq-reveal-index', index);
            });
        });

        if (reduceMotion || !('IntersectionObserver' in window)) {
            $items.addClass('is-revealed');
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -32px 0px' });

        $items.each(function () {
            observer.observe(this);
        });
    }

    /* 2. Header: .is-scrolled past a small threshold (rAF-throttled, passive).
          The state also applies with reduced motion; CSS makes it instant. */
    function initHeader() {
        var $header = $('[data-header]');
        var ticking = false;

        if (!$header.length) {
            return;
        }

        function update() {
            $header.toggleClass('is-scrolled', window.scrollY > 24);
            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(update);
            }
        }, { passive: true });

        update();
    }

    /* 3. Logo glint: once shortly after load, then on hover/focus of
          [data-glint-trigger]. A sweep in progress is never restarted. */
    function glint($glint) {
        if (reduceMotion || !$glint.length || $glint.hasClass('is-glinting')) {
            return;
        }

        $glint.addClass('is-glinting');
    }

    function initGlint() {
        $(document).on('animationend', '.yq-glint__shine', function (event) {
            if (event.originalEvent.animationName === 'yq-glint') {
                $(this).closest('.yq-glint').removeClass('is-glinting');
            }
        });

        $(document).on('mouseenter focusin', '[data-glint-trigger]', function () {
            glint($(this).find('.yq-glint'));
        });

        window.setTimeout(function () {
            glint($('[data-glint-trigger] .yq-glint'));
        }, 500);
    }

    /* 4. Offcanvas menu: items stagger in on open, reset once fully hidden.
          Bootstrap events are native, so they are bound without jQuery. */
    function initOffcanvas() {
        $('.yq-offcanvas').addClass('has-stagger').find('.yq-mobile-nav > li').each(function (index) {
            this.style.setProperty('--yq-reveal-index', index);
        });

        document.addEventListener('show.bs.offcanvas', function (event) {
            $(event.target).filter('.yq-offcanvas').addClass('is-entering');
        });

        document.addEventListener('shown.bs.offcanvas', function (event) {
            glint($(event.target).find('.yq-glint'));
        });

        document.addEventListener('hidden.bs.offcanvas', function (event) {
            $(event.target).removeClass('is-entering');
        });
    }

    $(function () {
        initReveal();
        initHeader();
        initGlint();
        initOffcanvas();
    });
})(jQuery);
