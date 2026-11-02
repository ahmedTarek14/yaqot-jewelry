/*
 * Yaqot dashboard behaviour (jQuery 3.7.1, loaded before this file).
 *
 * 1. AJAX forms: <form class="ajax-form" method="POST" action="..."> (add @method('PUT') for updates).
 *    - 2xx JSON  { message, redirect? } -> success toast, then optional redirect.
 *    - 422 JSON  { message, errors: { field: [msg, ...] } } -> every message under its field;
 *                messages for keys with no matching field go in an alert at the top of the form.
 *    - 419       -> session expired toast.
 *    - other     -> generic error toast (the server logs the exception).
 *    Fields inside a hidden ar/en tab: the tab opens on the first error, and every tab with
 *    errors is flagged (.yq-tab-error).
 * 2. Shared delete modal: buttons with data-bs-target="#delete-modal" data-url="..." data-name="...".
 * 3. Toasts: window.YaqotAdmin.toast(message, 'success' | 'danger', persistent). A flashed error
 *    (e.g. a refused delete) stays until closed; other toasts hide after 4s.
 * 4. Index switches: <input data-toggle-url="..."> sends a PATCH; the reply { value, label, message }
 *    updates the switch and its [data-toggle-label] badge. On failure the switch is put back.
 * 5. Image inputs: [data-image-input] previews the chosen file before it is uploaded.
 * 6. Multiselects: [data-multiselect] filters its checkboxes as you type and updates the count.
 * 7. Galleries: [data-gallery] adds, reorders (drag or buttons) and marks images for removal;
 *    the form then submits images[], images_order[] and remove_images[] (see image-gallery.blade.php).
 *
 * Validation errors for a group of inputs (images.2, categories.0) show under the element whose
 * data-field-errors lists the base name ("images", "categories"), when there is one.
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

    // persistent: stays until closed (used for a flashed error such as a refused delete,
    // which has nowhere else on the page to be read). Every other toast hides after 4s.
    function toast(message, type, persistent) {
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
        bootstrap.Toast.getOrCreateInstance($el[0], { delay: 4000, autohide: !persistent }).show();
    }

    /* ----------------------------------------------------- Validation errors */

    // "images.0" -> ["images[0]", "images[]"], "name_ar" -> ["name_ar", "name_ar[]"]
    function candidateNames(key) {
        var parts = key.split('.');
        if (parts.length === 1) {
            return [key, key + '[]'];
        }
        var bracketed = parts[0] + $.map(parts.slice(1), function (p) { return '[' + p + ']'; }).join('');
        return [bracketed, parts[0] + '[]'];
    }

    function findField($form, key) {
        // A group (multiselect, gallery) takes the errors of all its inputs.
        var $group = $form.find('[data-field-errors~="' + CSS.escape(key.split('.')[0]) + '"]').first();
        if ($group.length) {
            return $group;
        }

        var names = candidateNames(key);
        for (var i = 0; i < names.length; i++) {
            // A switch has a hidden "0" input with the same name; the error belongs to the visible one.
            var $field = $form.find('[name="' + CSS.escape(names[i]) + '"]').not('[type="hidden"]').first();
            if ($field.length) {
                return $field;
            }
        }
        return null;
    }

    function clearErrors(form) {
        var $form = $(form);
        $form.find('.is-invalid').each(function () {
            var $field = $(this).removeClass('is-invalid').removeAttr('aria-invalid');
            var original = $field.data('describedby');
            if (original) {
                $field.attr('aria-describedby', original);
            } else {
                $field.removeAttr('aria-describedby');
            }
        });
        $form.find('.yq-errors').remove();
        $form.find('.yq-tab-error').prop('hidden', true);
    }

    // Flag every ar/en tab that contains an invalid field.
    function flagTabs($form) {
        $form.find('.tab-pane').each(function () {
            var $pane = $(this);
            if ($pane.find('.is-invalid').length) {
                $('[data-bs-target="#' + $pane.attr('id') + '"]').find('.yq-tab-error').prop('hidden', false);
            }
        });
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

            // Keep the field's help text described too; clearErrors restores the original value.
            var described = $field.attr('aria-describedby') || '';
            if ($field.data('describedby') === undefined) {
                $field.data('describedby', described);
            }
            $field.addClass('is-invalid').attr({
                'aria-invalid': 'true',
                'aria-describedby': (($field.data('describedby') || '') + ' ' + id).trim()
            });

            // Place the messages after the input group / form-check wrapper when there is one.
            // A group with several errors lists them in order.
            var $anchor = $field.closest('.input-group, .form-check');
            $anchor = $anchor.length ? $anchor : $field;
            var $previous = $anchor.nextUntil(':not(.invalid-feedback.yq-errors)').last();
            ($previous.length ? $previous : $anchor).after($box);

            $first = $first || $field;
        });

        var $alert = showFormErrors($form, unmatched);
        if ($alert && !$first) {
            $alert.trigger('focus');
        }

        flagTabs($form);

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
        // Soft UI adds a .ripple span on click and removes it 600ms later through its
        // parentElement: keep it attached while the label changes, and never save it in the label.
        var $ripples = $button.children('.ripple').detach();
        if (busy) {
            $button.data('label', $button.html()).prop('disabled', true).attr('aria-busy', 'true')
                .text(t('saving', 'Saving…'));
        } else {
            $button.prop('disabled', false).removeAttr('aria-busy');
            if ($button.data('label')) {
                $button.html($button.data('label'));
            }
        }
        $button.append($ripples);
    }

    function onSuccess(data) {
        toast(data.message || t('saved', 'Saved.'), 'success');
        if (data.redirect) {
            setTimeout(function () { window.location.assign(data.redirect); }, 600);
            return true;
        }
        return false;
    }

    // MB in the page's language, from the ":value MB" template the server sends.
    function megabytes(bytes) {
        var value = Math.round((bytes / 1048576) * 10) / 10;

        return t('megabytes', ':value MB').replace(':value', String(value));
    }

    // The files of a form against the server's real upload limits (the same numbers PHP enforces).
    // Returns the message to show, or null when everything fits. Nothing is sent when it does not:
    // PHP would otherwise drop the whole request and only a 413 would come back.
    function uploadProblem(data) {
        var limits = i18n.uploadLimits || {};
        var total = 0;
        var problem = null;

        data.forEach(function (value) {
            if (!(window.File && value instanceof window.File) || !value.size) {
                return;
            }

            total += value.size;

            if (!problem && limits.file > 0 && value.size > limits.file) {
                problem = t('fileTooBig', '').replace(':name', value.name).replace(':size', megabytes(value.size)).replace(':limit', megabytes(limits.file));
            }
        });

        if (!problem && limits.post > 0 && total + 65536 > limits.post) {
            problem = t('postTooBig', '').replace(':total', megabytes(total)).replace(':limit', megabytes(limits.post));
        }

        return problem;
    }

    $(document).on('submit', '.ajax-form', function (event) {
        event.preventDefault();

        var form = this;
        var $button = $(form).find('[type="submit"]').first();
        var payload = new FormData(form);
        var tooBig = uploadProblem(payload);

        if (tooBig) {
            toast(tooBig, 'danger', true);
            return;
        }

        clearErrors(form);
        setBusy($button, true);

        $.ajax({
            url: form.action,
            method: 'POST', // PUT/PATCH/DELETE are sent through the _method field
            data: payload,
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
                } else if (xhr.status === 413) {
                    // Larger than the server's post_max_size: nothing was saved.
                    toast(t('tooLarge', 'The upload is too large.'), 'danger', true);
                } else {
                    toast(t('error', 'Unexpected error.'), 'danger');
                }
                setBusy($button, false);
            });
    });

    /* ---------------------------------------------------------- Delete modal */

    $('#delete-modal').on('show.bs.modal', function (event) {
        var $trigger = $(event.relatedTarget || []);
        $('#delete-form').attr('action', $trigger.attr('data-url') || '');

        // The question names the item; the name goes in through .text(). A row may bring its own
        // question (data-confirm, e.g. "Move … to the trash?"). split/join: no $-patterns from the name.
        var question = $trigger.attr('data-confirm') || t('deleteConfirm', 'Delete “:name”?');
        $('#delete-modal-body').text(question.split(':name').join($trigger.attr('data-name') || ''));
    });

    /* -------------------------------------------------------- Index switches */

    $(document).on('change', '[data-toggle-url]', function () {
        var $input = $(this);
        var wanted = $input.prop('checked');
        var $label = $input.closest('.yq-status-toggle').find('[data-toggle-label]');

        $input.prop('disabled', true);

        $.ajax({ url: $input.attr('data-toggle-url'), method: 'POST', data: { _method: 'PATCH' } })
            .done(function (data) {
                var on = !!(data && data.value);
                $input.prop('checked', on);
                $label.text(data.label || '')
                    .toggleClass('yq-badge--on', on)
                    .toggleClass('yq-badge--off', !on);
                toast(data.message, 'success');
            })
            .fail(function (xhr) {
                $input.prop('checked', !wanted);
                // 409 = an expected refusal with its own explanation (e.g. the last active admin).
                var refusal = xhr.status === 409 && xhr.responseJSON && xhr.responseJSON.message;
                toast(refusal || (xhr.status === 419 ? t('sessionExpired', 'Session expired.') : t('error', 'Unexpected error.')), 'danger', !!refusal);
            })
            .always(function () {
                $input.prop('disabled', false).trigger('focus');
            });
    });

    /* --------------------------------------------------------- Image inputs */

    $(document).on('change', '[data-image-file]', function () {
        var $box = $(this).closest('[data-image-input]');
        var $preview = $box.find('[data-image-preview]');
        var $empty = $box.find('[data-image-empty]');
        var file = this.files && this.files[0];

        var previous = $preview.data('objectUrl');
        if (previous) {
            URL.revokeObjectURL(previous);
            $preview.removeData('objectUrl');
        }

        // Only preview what the browser can show; anything else is left to the server's validation
        // message, and the box goes back to the saved image (or the placeholder).
        if (!file || !/^image\/(jpeg|png|webp)$/.test(file.type)) {
            showSavedImage($box);
            return;
        }

        var url = URL.createObjectURL(file);
        $preview.data('objectUrl', url)
            .attr({ src: url, alt: $preview.attr('data-preview-alt') || '' })
            .prop('hidden', false)
            .removeClass('is-removed');
        $empty.prop('hidden', true);
        $box.find('[data-image-remove]').prop('checked', false);
    });

    function showSavedImage($box) {
        var $preview = $box.find('[data-image-preview]');
        var saved = $preview.attr('data-saved-src');

        if (saved) {
            $preview.attr({ src: saved, alt: $preview.attr('data-saved-alt') || '' }).prop('hidden', false);
        } else {
            $preview.prop('hidden', true).removeAttr('src');
        }
        $box.find('[data-image-empty]').prop('hidden', !!saved);
    }

    // A file with an image type that still fails to decode. "error" does not bubble, so bind directly.
    $('[data-image-preview]').on('error', function () {
        var $box = $(this).closest('[data-image-input]');
        if ($(this).attr('src') === $(this).attr('data-saved-src')) {
            $(this).prop('hidden', true);
            $box.find('[data-image-empty]').prop('hidden', false);
            return;
        }
        showSavedImage($box);
    });

    $(document).on('change', '[data-image-remove]', function () {
        $(this).closest('[data-image-input]').find('[data-image-preview]').toggleClass('is-removed', this.checked);
    });

    /* ---------------------------------------------------------- Multiselects */

    // Case-insensitive, and Arabic letters that are often typed interchangeably match each other.
    function foldText(text) {
        return (text || '').toLocaleLowerCase()
            .replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي')
            .replace(/[ً-ْـ]/g, '')
            .trim();
    }

    // The search box is useless without this script, so it is shown only now.
    $('[data-multiselect-search]').prop('hidden', false);

    $(document).on('input', '[data-multiselect-search]', function () {
        var $box = $(this).closest('[data-multiselect]');
        var query = foldText(this.value);
        var shown = 0;

        $box.find('[data-multiselect-option]').each(function () {
            var match = !query || foldText($(this).find('label').text()).indexOf(query) !== -1;
            $(this).prop('hidden', !match);
            shown += match ? 1 : 0;
        });
        $box.find('[data-multiselect-none]').prop('hidden', shown > 0);
    });

    // Enter in the search box filters; it must not submit the form.
    $(document).on('keydown', '[data-multiselect-search]', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
        }
    });

    $(document).on('change', '[data-multiselect] input[type="checkbox"]', function () {
        var $box = $(this).closest('[data-multiselect]');
        var count = $box.find('input[type="checkbox"]:checked').length;
        $box.find('[data-multiselect-count]').text(t('multiselectSelected', ':count').replace(':count', count));
    });

    /* ------------------------------------------------------------- Galleries */

    // New files are kept on their tiles and copied back into the file input in gallery order.
    // Without DataTransfer the input is left alone: its files are uploaded and added at the end.
    var canSetFiles = false;
    try {
        canSetFiles = !!new DataTransfer();
    } catch (e) { /* older browser */ }

    function fill(key, fallback, values) {
        var text = t(key, fallback);
        $.each(values || {}, function (name, value) {
            text = text.split(':' + name).join(String(value));
        });
        return text;
    }

    function announce($gallery, message) {
        var $status = $gallery.find('[data-gallery-status]').text('');
        // Cleared first, so the same message is read again.
        setTimeout(function () { $status.text(message); }, 50);
    }

    // Everything that depends on position: tokens, the file input, labels and the primary badge.
    function refreshGallery($gallery) {
        var $items = $gallery.find('[data-gallery-item]');
        var total = $items.length;
        var transfer = canSetFiles ? new DataTransfer() : null;
        var newIndex = 0;
        var primaryShown = false;

        $items.each(function (index) {
            var $item = $(this);
            var values = { position: index + 1 };
            var removed = $item.hasClass('is-removed');
            var isNew = $item.is('[data-gallery-new]');
            var isPrimary = !removed && !primaryShown;

            if (isNew) {
                $item.find('[data-gallery-token]').val('new:' + newIndex);
                newIndex++;
                if (transfer) {
                    transfer.items.add($item.data('file'));
                }
            }

            primaryShown = primaryShown || isPrimary;
            $item.find('[data-gallery-primary]').prop('hidden', !isPrimary);
            $item.find('[data-gallery-img]').attr('alt', fill('gallery_image', 'Image :position', values));
            $item.find('[data-gallery-move="-1"]').prop('disabled', index === 0)
                .attr('aria-label', fill('gallery_move_earlier', 'Move image :position earlier', values));
            $item.find('[data-gallery-move="1"]').prop('disabled', index === total - 1)
                .attr('aria-label', fill('gallery_move_later', 'Move image :position later', values));
            $item.find('[data-gallery-remove]').attr('aria-label', isNew
                ? fill('gallery_discard', 'Discard new image :position', values)
                : fill(removed ? 'gallery_keep' : 'gallery_remove', removed ? 'Keep image :position' : 'Remove image :position', values));
        });

        if (transfer) {
            $gallery.find('[data-gallery-file]')[0].files = transfer.files;
        }
        $gallery.find('[data-gallery-empty]').prop('hidden', total > 0);
    }

    function announceMove($gallery, $item) {
        announce($gallery, fill('gallery_moved', 'Image moved to position :position of :total.', {
            position: $item.index() + 1,
            total: $gallery.find('[data-gallery-item]').length
        }));
    }

    $('[data-gallery]').each(function () {
        refreshGallery($(this));
    });

    // Some systems report no type for .webp: fall back to the extension (the server checks the content).
    function isGalleryImage(file) {
        return /^image\/(jpeg|png|webp)$/.test(file.type) || (!file.type && /\.(jpe?g|png|webp)$/i.test(file.name));
    }

    // Files picked in the input or dropped onto the gallery become new tiles.
    function addGalleryFiles($gallery, files) {
        var template = $gallery.find('[data-gallery-template]')[0];
        var $list = $gallery.find('[data-gallery-list]');
        var added = 0;

        $.each(Array.prototype.slice.call(files || []), function (i, file) {
            if (!isGalleryImage(file)) {
                toast(fill('gallery_type_rejected', '“:name” is not a JPG, PNG or WebP image.', { name: file.name }), 'danger');
                return;
            }
            var url = URL.createObjectURL(file);
            var $item = $(template.content.firstElementChild.cloneNode(true)).data({ file: file, objectUrl: url });
            $item.find('[data-gallery-img]').attr('src', url);
            $list.append($item);
            added++;
        });

        refreshGallery($gallery);
        if (added) {
            announce($gallery, fill('gallery_added', 'New images added: :count.', { count: added }));
        }
    }

    $(document).on('change', '[data-gallery-file]', function () {
        // Without DataTransfer the input keeps its own files (added at the end on save).
        if (canSetFiles) {
            // this.files holds only the new selection; earlier new files live on their tiles.
            addGalleryFiles($(this).closest('[data-gallery]'), this.files);
        }
    });

    function hasFiles(event) {
        var types = event.originalEvent.dataTransfer && event.originalEvent.dataTransfer.types;
        return !!types && Array.prototype.indexOf.call(types, 'Files') !== -1;
    }

    // Files dragged in from the desktop: never let the browser open them (and leave the
    // unsaved form); add them like a selection in the input.
    $(document).on('dragover', '[data-gallery]', function (event) {
        if (!$dragging && hasFiles(event)) {
            event.preventDefault();
            event.originalEvent.dataTransfer.dropEffect = canSetFiles ? 'copy' : 'none';
        }
    });

    $(document).on('drop', '[data-gallery]', function (event) {
        if (!$dragging && hasFiles(event)) {
            event.preventDefault();
            if (canSetFiles) {
                addGalleryFiles($(this), event.originalEvent.dataTransfer.files);
            }
        }
    });

    $(document).on('click', '[data-gallery-move]', function () {
        var $button = $(this);
        var offset = parseInt($button.attr('data-gallery-move'), 10);
        var $item = $button.closest('[data-gallery-item]');
        var $gallery = $item.closest('[data-gallery]');

        if (offset < 0) {
            $item.prev('[data-gallery-item]').before($item);
        } else {
            $item.next('[data-gallery-item]').after($item);
        }
        refreshGallery($gallery);

        // At either end this button is now disabled: keep focus on the tile's other arrow.
        ($button.prop('disabled') ? $item.find('[data-gallery-move="' + (-offset) + '"]') : $button).trigger('focus');
        announceMove($gallery, $item);
    });

    $(document).on('click', '[data-gallery-remove]', function () {
        var $item = $(this).closest('[data-gallery-item]');
        var $gallery = $item.closest('[data-gallery]');
        var position = $item.index() + 1;

        if ($item.is('[data-gallery-new]')) {
            URL.revokeObjectURL($item.data('objectUrl'));
            var $next = $item.next('[data-gallery-item]');
            $next = $next.length ? $next : $item.prev('[data-gallery-item]');
            $item.remove();
            refreshGallery($gallery);
            ($next.length ? $next.find('[data-gallery-remove]') : $gallery.find('[data-gallery-file]')).trigger('focus');
            announce($gallery, t('gallery_discarded', 'New image discarded.'));
            return;
        }

        // Saved images are only marked; the removal happens when the form is saved, and can be undone until then.
        var removed = !$item.hasClass('is-removed');
        $item.toggleClass('is-removed', removed);
        $item.find('[data-gallery-remove-input]').prop('disabled', !removed);
        $item.find('[data-gallery-removed-note]').prop('hidden', !removed);
        refreshGallery($gallery);
        announce($gallery, fill(removed ? 'gallery_marked' : 'gallery_kept', '', { position: position }));
    });

    // Drag to reorder (mouse); the arrow buttons do the same from the keyboard.
    var $dragging = null;

    $(document).on('dragstart', '[data-gallery-item]', function (event) {
        $dragging = $(this).addClass('is-dragging');
        var transfer = event.originalEvent.dataTransfer;
        transfer.effectAllowed = 'move';
        transfer.setData('text/plain', ''); // Firefox starts a drag only with data
    });

    $(document).on('dragover', '[data-gallery-item]', function (event) {
        if (!$dragging || this === $dragging[0] || this.parentNode !== $dragging[0].parentNode) {
            return;
        }
        event.preventDefault();
        event.originalEvent.dataTransfer.dropEffect = 'move';

        var rect = this.getBoundingClientRect();
        var before = event.originalEvent.clientX - rect.left < rect.width / 2;
        if ($(this).closest('[dir]').attr('dir') === 'rtl') {
            before = !before;
        }
        // Move only when the position changes, so the tiles do not flicker.
        if (before && this.previousElementSibling !== $dragging[0]) {
            $(this).before($dragging);
        } else if (!before && this.nextElementSibling !== $dragging[0]) {
            $(this).after($dragging);
        }
    });

    $(document).on('dragover drop', '[data-gallery-list]', function (event) {
        if ($dragging) {
            event.preventDefault();
        }
    });

    $(document).on('dragend', '[data-gallery-item]', function () {
        if (!$dragging) {
            return;
        }
        var $item = $dragging.removeClass('is-dragging');
        var $gallery = $item.closest('[data-gallery]');
        $dragging = null;
        refreshGallery($gallery);
        announceMove($gallery, $item);
    });

    /* ---------------------------------------------- Jewelry-only fields */

    // <select data-jewelry-toggle="jewelry"> (the product type) hides and disables every
    // [data-jewelry-only] field in its form (gold colour, stones) unless its value matches.
    // Disabled fields are not submitted; the server clears them for other types. The chosen
    // values stay in the controls, so switching the type back and forth loses nothing.
    function syncJewelryOnly($select) {
        var show = $select.val() === $select.attr('data-jewelry-toggle');
        $select.closest('form').find('[data-jewelry-only]').each(function () {
            var $field = $(this).prop('hidden', !show);
            $field.find('select').prop('disabled', !show);
        });
    }

    $(document).on('change', '[data-jewelry-toggle]', function () {
        syncJewelryOnly($(this));
    });
    $('[data-jewelry-toggle]').each(function () {
        syncJewelryOnly($(this));
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
        var flashType = $flash.attr('data-flash-type') || 'success';
        toast($flash.attr('data-flash-message'), flashType, flashType === 'danger');
    }

    window.YaqotAdmin = { toast: toast, showErrors: showErrors, clearErrors: clearErrors };
})(jQuery);
