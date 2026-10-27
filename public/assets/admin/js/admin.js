/*
 * Yaqot dashboard behaviour (jQuery 3.7.1, loaded before this file).
 *
 * 1. AJAX forms: <form class="ajax-form" method="POST" action="..."> (add @method('PUT') for updates).
 *    - 2xx JSON  { message, redirect? } -> success toast, then optional redirect.
 *    - 422 JSON  { message, errors: { field: [msg, ...] } } -> every message under its field;
 *                messages for keys with no matching field go in an alert at the top of the form.
 *    - 419       -> session expired toast.
 *    - other     -> generic error toast (the server logs the exception).
 * 2. Shared delete modal: buttons with data-bs-target="#delete-modal" data-url="...".
 * 3. Toasts: window.YaqotAdmin.toast(message, 'success' | 'danger').
 *
 * Bootstrap 5 (bootstrap.Toast, bootstrap.Tab) does not need jQuery; it is used directly.
 */
(function ($) {
    'use strict';

    var i18n = {};
    try {
        i18n = JSON.parse($('#yq-admin-i18n').text());
    } catch (e) { /* defaults below */ }

    function t(key, fallback) {
        return i18n[key] || fallback;
    }

    // Every request carries the CSRF token and asks for JSON. Set once, before any request.
    $.ajaxSetup({
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || ''
        }
    });

    /* ---------------------------------------------------------------- Toasts */

    function toast(message, type) {
        var $container = $('#yq-toasts');
        if (!$container.length || !message) {
            return;
        }

        // Server strings go through .text(), never as HTML.
        var $body = $('<div class="toast-body"></div>').text(message);
        var $close = $('<button type="button" class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>')
            .attr('aria-label', t('close', 'Close'));

        var $el = $('<div class="toast align-items-center border-0 text-white"></div>')
            .addClass(type === 'danger' ? 'bg-danger' : 'bg-dark')
            .attr('role', type === 'danger' ? 'alert' : 'status')
            .append($('<div class="d-flex"></div>').append($body, $close))
            .appendTo($container);

        $el.on('hidden.bs.toast', function () {
            $el.remove();
        });
        bootstrap.Toast.getOrCreateInstance($el[0], { delay: 4000 }).show();
    }

    /* ----------------------------------------------------- Validation errors */

    // "images.0" -> ["images[0]", "images[]"], "name_ar" -> ["name_ar"]
    function candidateNames(key) {
        var parts = key.split('.');
        if (parts.length === 1) {
            return [key];
        }
        var bracketed = parts[0] + $.map(parts.slice(1), function (p) { return '[' + p + ']'; }).join('');
        return [bracketed, parts[0] + '[]'];
    }

    function findField($form, key) {
        var names = candidateNames(key);
        for (var i = 0; i < names.length; i++) {
            var $field = $form.find('[name="' + CSS.escape(names[i]) + '"]').first();
            if ($field.length) {
                return $field;
            }
        }
        return null;
    }

    function clearErrors(form) {
        var $form = $(form);
        $form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
        $form.find('.yq-errors').remove();
    }

    // Messages whose field is not on the form (e.g. a server-only key) are never
    // dropped: they are listed in an alert at the top of the form instead.
    function showFormErrors($form, messages) {
        if (!messages.length) {
            return null;
        }
        var $alert = $('<div class="alert alert-danger text-white yq-errors yq-form-errors" role="alert" tabindex="-1"></div>');
        $.each(messages, function (i, message) {
            $('<p class="mb-0"></p>').text(message).appendTo($alert);
        });
        $form.prepend($alert);
        return $alert;
    }

    function showErrors(form, errors, fallbackMessage) {
        var $form = $(form);
        var $first = null;
        var unmatched = [];

        if ($.isEmptyObject(errors) && fallbackMessage) {
            unmatched.push(fallbackMessage);
        }

        $.each(errors, function (key, messages) {
            var $field = findField($form, key);
            if (!$field) {
                unmatched = unmatched.concat(messages);
                return;
            }

            var id = 'error-' + key.replace(/[^\w-]/g, '-');
            var $box = $('<div class="invalid-feedback yq-errors"></div>').attr('id', id);
            $.each(messages, function (i, message) {
                $('<p></p>').text(message).appendTo($box);
            });

            $field.addClass('is-invalid').attr({ 'aria-invalid': 'true', 'aria-describedby': id });

            // Place the messages after the input group / form-check wrapper when there is one.
            var $anchor = $field.closest('.input-group, .form-check');
            ($anchor.length ? $anchor : $field).after($box);

            $first = $first || $field;
        });

        var $alert = showFormErrors($form, unmatched);
        if ($alert && !$first) {
            $alert.trigger('focus');
        }

        if ($first) {
            // Reveal a field that sits in a hidden tab (e.g. the English tab).
            var $pane = $first.closest('.tab-pane');
            if ($pane.length && !$pane.hasClass('active')) {
                var $trigger = $('[data-bs-target="#' + $pane.attr('id') + '"]').first();
                if ($trigger.length) {
                    bootstrap.Tab.getOrCreateInstance($trigger[0]).show();
                }
            }
            $first.trigger('focus');
        }
    }

    /* ------------------------------------------------------------ AJAX forms */

    function setBusy($button, busy) {
        if (!$button.length) {
            return;
        }
        if (busy) {
            $button.data('label', $button.html()).prop('disabled', true).text(t('saving', 'Saving…'));
        } else {
            $button.prop('disabled', false);
            if ($button.data('label')) {
                $button.html($button.data('label'));
            }
        }
    }

    function onSuccess(data) {
        toast(data.message || t('saved', 'Saved.'), 'success');
        if (data.redirect) {
            setTimeout(function () { window.location.assign(data.redirect); }, 600);
            return true;
        }
        return false;
    }

    $(document).on('submit', '.ajax-form', function (event) {
        event.preventDefault();

        var form = this;
        var $button = $(form).find('[type="submit"]').first();

        clearErrors(form);
        setBusy($button, true);

        $.ajax({
            url: form.action,
            method: 'POST', // PUT/PATCH/DELETE are sent through the _method field
            data: new FormData(form),
            processData: false,
            contentType: false
        })
            .done(function (data) {
                if (onSuccess($.isPlainObject(data) ? data : {})) {
                    return;
                }
                setBusy($button, false);
            })
            .fail(function (xhr) {
                var data = $.isPlainObject(xhr.responseJSON) ? xhr.responseJSON : {};

                if (xhr.status >= 200 && xhr.status < 300) {
                    // 2xx with an unparsable body: still a success.
                    if (onSuccess(data)) {
                        return;
                    }
                } else if (xhr.status === 422) {
                    showErrors(form, data.errors || {}, data.message);
                    toast(t('validationFailed', 'Please review the highlighted fields.'), 'danger');
                } else if (xhr.status === 419) {
                    toast(t('sessionExpired', 'Session expired.'), 'danger');
                } else {
                    toast(t('error', 'Unexpected error.'), 'danger');
                }
                setBusy($button, false);
            });
    });

    /* ---------------------------------------------------------- Delete modal */

    $('#delete-modal').on('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        $('#delete-form').attr('action', (trigger && $(trigger).attr('data-url')) || '');
    });

    /* ------------------------------------------------------ Sidebar a11y */

    // Soft UI toggles the mobile sidebar by adding g-sidenav-pinned to <body>;
    // mirror that state on the toggle buttons for assistive technology.
    var $togglers = $('#iconNavbarSidenav, #iconSidenav');
    $togglers.attr('aria-expanded', 'false').on('click', function () {
        setTimeout(function () {
            $togglers.attr('aria-expanded', String($('body').hasClass('g-sidenav-pinned')));
        }, 0);
    });

    /* --------------------------------------------------- Flash on load */

    var $flash = $('[data-flash-message]').first();
    if ($flash.length) {
        toast($flash.attr('data-flash-message'), $flash.attr('data-flash-type') || 'success');
    }

    window.YaqotAdmin = { toast: toast, showErrors: showErrors, clearErrors: clearErrors };
})(jQuery);
