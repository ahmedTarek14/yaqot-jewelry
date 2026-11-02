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
          viewport. Items of a [data-reveal-group] are numbered for the stagger
          in the order they come into view together, so a lower row of a long
          product grid starts at once instead of waiting for the rows above it. */
    function initReveal() {
        var $items = $('[data-reveal]');

        if (reduceMotion || !('IntersectionObserver' in window)) {
            $items.addClass('is-revealed');
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            var counts = new Map();

            // Entries arrive in observation (document) order.
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                var group = entry.target.parentElement && entry.target.parentElement.closest('[data-reveal-group]');

                if (group) {
                    var index = counts.get(group) || 0;

                    entry.target.style.setProperty('--yq-reveal-index', index);
                    counts.set(group, index + 1);
                }

                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
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

    /* 5. Filter forms (GET, [data-filter-form]): empty fields are left out of the
          URL, so shared links stay short; a [data-autosubmit] control (the sort,
          tied to the form with form="") submits on change. */
    function initFilterForms() {
        $(document).on('submit', '[data-filter-form]', function () {
            $(this.elements).filter(function () {
                return this.name && this.value === '' && (this.type !== 'radio' || this.checked);
            }).prop('disabled', true);
        });

        $(document).on('change', '[data-autosubmit]', function () {
            if (this.form) {
                $(this.form).trigger('submit');
            }
        });

        // Coming back with the Back button may restore the page with those fields still disabled.
        window.addEventListener('pageshow', function () {
            $('[data-filter-form]').each(function () {
                $(this.elements).prop('disabled', false);
            });
        });
    }

    /* 6. Product gallery ([data-gallery]): the thumbnail buttons swap the main image.
          Arrow keys move between thumbnails (roving tabindex; Left/Right follow the
          reading direction), Home/End jump to the ends, Enter/Space is the button's
          click. The caption ("Image 2 of 5", template from data-caption) is an
          aria-live region. No motion: the image changes in place. */
    function initGallery() {
        $('[data-gallery]').each(function () {
            var $gallery = $(this);
            var $main = $gallery.find('[data-gallery-main]');
            var $caption = $gallery.find('[data-gallery-caption]');
            var $thumbs = $gallery.find('[data-gallery-thumb]');
            var template = $gallery.attr('data-caption') || '';

            if ($thumbs.length < 2) {
                return;
            }

            function select(index) {
                var $thumb = $thumbs.eq(index);

                $thumbs.attr({ 'aria-pressed': 'false', tabindex: '-1' }).removeAttr('aria-current');
                $thumb.attr({ 'aria-pressed': 'true', tabindex: '0', 'aria-current': 'true' });
                $main.attr('src', $thumb.attr('data-image-url'));
                $caption.text(template.replace(':current', index + 1).replace(':total', $thumbs.length));
            }

            $thumbs.on('click', function () {
                select($thumbs.index(this));
            });

            $thumbs.on('keydown', function (event) {
                var count = $thumbs.length;
                var index = $thumbs.index(this);
                var rtl = window.getComputedStyle(this).direction === 'rtl';
                var next;

                if (event.key === 'ArrowRight') {
                    next = index + (rtl ? -1 : 1);
                } else if (event.key === 'ArrowLeft') {
                    next = index + (rtl ? 1 : -1);
                } else if (event.key === 'ArrowDown') {
                    next = index + 1;
                } else if (event.key === 'ArrowUp') {
                    next = index - 1;
                } else if (event.key === 'Home') {
                    next = 0;
                } else if (event.key === 'End') {
                    next = count - 1;
                } else {
                    return;
                }

                event.preventDefault();
                next = (next + count) % count;
                select(next);
                $thumbs.eq(next).trigger('focus');
            });
        });
    }

    /* 7. Hero slider ([data-hero-carousel], Bootstrap's carousel). Autoplay (7s) runs only
          when nothing says stop: the visitor's pause button, a pointer (mouse) over the hero,
          keyboard focus inside it, a hidden tab, the hero scrolled out of view, or
          prefers-reduced-motion (then it starts paused; the button can start it).
          Pausing also stops the ambient motion (.is-paused, CSS). Swipe and the arrow keys are
          Bootstrap's, which follow the page direction. Images of the other slides are lazy;
          they are fetched once the page has loaded, so the first crossfade shows them. */
    function initHero() {
        var el = document.querySelector('[data-hero-carousel]');
        var hero = document.querySelector('[data-hero]');

        if (!el || !hero || !window.bootstrap) {
            return;
        }

        var carousel = window.bootstrap.Carousel.getOrCreateInstance(el, {
            interval: 7000,
            ride: false,
            pause: false,
            wrap: true,
            touch: true,
            keyboard: true
        });
        var $toggle = $('[data-hero-toggle]');
        var inner = el.querySelector('.carousel-inner');
        var state = { userPaused: reduceMotion, pointer: false, focus: false, hidden: document.hidden, visible: true };

        function sync() {
            var run = !state.userPaused && !state.pointer && !state.focus && !state.hidden && state.visible;
            var label = state.userPaused ? 'play' : 'pause';

            if (run) {
                carousel.cycle();
            } else {
                carousel.pause();
            }

            hero.classList.toggle('is-paused', state.userPaused);
            $toggle.attr('aria-label', $toggle.attr('data-label-' + label));
            // Rotating slides are announced only while the visitor drives them.
            inner.setAttribute('aria-live', run ? 'off' : 'polite');
        }

        $toggle.on('click', function () {
            state.userPaused = !state.userPaused;
            sync();
        });

        $(hero).on('pointerenter', function (event) {
            if (event.originalEvent.pointerType === 'mouse') {
                state.pointer = true;
                sync();
            }
        }).on('pointerleave', function () {
            state.pointer = false;
            sync();
        }).on('focusin', function (event) {
            // Keyboard focus only: a mouse click on the play button must not keep it paused.
            state.focus = event.target.matches(':focus-visible');
            sync();
        }).on('focusout', function (event) {
            // Focus moving between two controls inside the hero is not "leaving".
            state.focus = hero.contains(event.originalEvent.relatedTarget);
            sync();
        });

        document.addEventListener('visibilitychange', function () {
            state.hidden = document.hidden;
            sync();
        });

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                state.visible = entries[entries.length - 1].isIntersecting;
                sync();
            }, { threshold: 0.25 }).observe(hero);
        }

        function warm() {
            $(el).find('img[loading="lazy"]').attr('loading', 'eager');
        }

        el.addEventListener('slide.bs.carousel', warm);

        // jQuery runs its ready callbacks late, so the page may already be loaded by now.
        if (document.readyState === 'complete') {
            window.setTimeout(warm, 1200);
        } else {
            window.addEventListener('load', function () {
                window.setTimeout(warm, 1200);
            });
        }

        sync();
    }

    $(function () {
        initHero();
        initReveal();
        initHeader();
        initGlint();
        initOffcanvas();
        initFilterForms();
        initGallery();
    });
})(jQuery);
