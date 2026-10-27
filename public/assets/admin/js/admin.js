/*
 * Yaqot dashboard behaviour (vanilla JS, no jQuery).
 *
 * 1. AJAX forms: <form class="ajax-form" method="POST" action="..."> (add @method('PUT') for updates).
 *    - 2xx JSON  { message, redirect? } -> success toast, then optional redirect.
 *    - 422 JSON  { message, errors: { field: [msg, ...] } } -> every message under its field.
 *    - 419       -> session expired toast.
 *    - other     -> generic error toast (the server logs the exception).
 * 2. Shared delete modal: buttons with data-bs-target="#delete-modal" data-url="...".
 * 3. Toasts: window.YaqotAdmin.toast(message, 'success' | 'danger').
 */
(function () {
    'use strict';

    var i18n = {};
    try {
        i18n = JSON.parse(document.getElementById('yq-admin-i18n').textContent);
    } catch (e) { /* defaults below */ }

    function t(key, fallback) {
        return i18n[key] || fallback;
    }

    /* ---------------------------------------------------------------- Toasts */

    function toast(message, type) {
        var container = document.getElementById('yq-toasts');
        if (!container || !message) {
            return;
        }

        var el = document.createElement('div');
        el.className = 'toast align-items-center border-0 text-white ' + (type === 'danger' ? 'bg-danger' : 'bg-dark');
        el.setAttribute('role', type === 'danger' ? 'alert' : 'status');

        var wrap = document.createElement('div');
        wrap.className = 'd-flex';

        var body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = message;

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close btn-close-white m-auto me-2';
        close.setAttribute('data-bs-dismiss', 'toast');
        close.setAttribute('aria-label', t('close', 'Close'));

        wrap.appendChild(body);
        wrap.appendChild(close);
        el.appendChild(wrap);
        container.appendChild(el);

        el.addEventListener('hidden.bs.toast', function () {
            el.remove();
        });
        bootstrap.Toast.getOrCreateInstance(el, { delay: 4000 }).show();
    }

    /* ----------------------------------------------------- Validation errors */

    // "images.0" -> ["images[0]", "images[]"], "name_ar" -> ["name_ar"]
    function candidateNames(key) {
        var parts = key.split('.');
        if (parts.length === 1) {
            return [key];
        }
        var bracketed = parts[0] + parts.slice(1).map(function (p) { return '[' + p + ']'; }).join('');
        return [bracketed, parts[0] + '[]'];
    }

    function findField(form, key) {
        var names = candidateNames(key);
        for (var i = 0; i < names.length; i++) {
            var field = form.querySelector('[name="' + CSS.escape(names[i]) + '"]');
            if (field) {
                return field;
            }
        }
        return null;
    }

    function clearErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
            el.removeAttribute('aria-invalid');
        });
        form.querySelectorAll('.yq-errors').forEach(function (el) {
            el.remove();
        });
    }

    function showErrors(form, errors) {
        var first = null;

        Object.keys(errors).forEach(function (key) {
            var field = findField(form, key);
            if (!field) {
                return;
            }

            var box = document.createElement('div');
            box.className = 'invalid-feedback yq-errors';
            box.id = 'error-' + key.replace(/[^\w-]/g, '-');
            errors[key].forEach(function (message) {
                var p = document.createElement('p');
                p.textContent = message;
                box.appendChild(p);
            });

            field.classList.add('is-invalid');
            field.setAttribute('aria-invalid', 'true');
            field.setAttribute('aria-describedby', box.id);

            // Place the messages after the input group / form-check wrapper when there is one.
            var anchor = field.closest('.input-group, .form-check') || field;
            anchor.insertAdjacentElement('afterend', box);

            first = first || field;
        });

        if (first) {
            // Reveal a field that sits in a hidden tab (e.g. the English tab).
            var pane = first.closest('.tab-pane');
            if (pane && !pane.classList.contains('active')) {
                var trigger = document.querySelector('[data-bs-target="#' + pane.id + '"]');
                if (trigger) {
                    bootstrap.Tab.getOrCreateInstance(trigger).show();
                }
            }
            first.focus();
        }
    }

    /* ------------------------------------------------------------ AJAX forms */

    function setBusy(button, busy) {
        if (!button) {
            return;
        }
        if (busy) {
            button.dataset.label = button.innerHTML;
            button.disabled = true;
            button.textContent = t('saving', 'Saving…');
        } else {
            button.disabled = false;
            if (button.dataset.label) {
                button.innerHTML = button.dataset.label;
            }
        }
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.classList.contains('ajax-form')) {
            return;
        }
        event.preventDefault();

        var button = form.querySelector('[type="submit"]');
        var token = document.querySelector('meta[name="csrf-token"]');

        clearErrors(form);
        setBusy(button, true);

        fetch(form.action, {
            method: 'POST', // PUT/PATCH/DELETE are sent through the _method field
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token ? token.content : ''
            }
        })
            .then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (data) {
                    return { status: response.status, ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                if (result.ok) {
                    toast(result.data.message || t('saved', 'Saved.'), 'success');
                    if (result.data.redirect) {
                        setTimeout(function () { window.location.assign(result.data.redirect); }, 600);
                        return;
                    }
                } else if (result.status === 422) {
                    showErrors(form, result.data.errors || {});
                    toast(t('validationFailed', 'Please review the highlighted fields.'), 'danger');
                } else if (result.status === 419) {
                    toast(t('sessionExpired', 'Session expired.'), 'danger');
                } else {
                    toast(t('error', 'Unexpected error.'), 'danger');
                }
                setBusy(button, false);
            })
            .catch(function () {
                toast(t('error', 'Unexpected error.'), 'danger');
                setBusy(button, false);
            });
    });

    /* ---------------------------------------------------------- Delete modal */

    var deleteModal = document.getElementById('delete-modal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            document.getElementById('delete-form').action = trigger ? trigger.getAttribute('data-url') : '';
        });
    }

    /* ------------------------------------------------------ Sidebar a11y */

    // Soft UI toggles the mobile sidebar by adding g-sidenav-pinned to <body>;
    // mirror that state on the toggle buttons for assistive technology.
    var togglers = document.querySelectorAll('#iconNavbarSidenav, #iconSidenav');
    togglers.forEach(function (button) {
        button.setAttribute('aria-expanded', 'false');
        button.addEventListener('click', function () {
            setTimeout(function () {
                var open = document.body.classList.contains('g-sidenav-pinned');
                togglers.forEach(function (b) { b.setAttribute('aria-expanded', String(open)); });
            }, 0);
        });
    });

    /* --------------------------------------------------- Flash on load */

    var flash = document.querySelector('[data-flash-message]');
    if (flash) {
        toast(flash.getAttribute('data-flash-message'), flash.getAttribute('data-flash-type') || 'success');
    }

    window.YaqotAdmin = { toast: toast, showErrors: showErrors, clearErrors: clearErrors };
})();
