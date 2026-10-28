{{-- One tile of <x-admin.image-gallery>: the image, its "primary" badge and its controls. admin.js fills alt texts and labels. --}}
<div class="yq-gallery__frame">
    <img @if ($src) src="{{ $src }}" @endif alt="" draggable="false" data-gallery-img>
</div>
<span class="yq-gallery__primary" data-gallery-primary hidden>{{ __('admin.form.gallery.primary') }}</span>
<span class="yq-gallery__note" data-gallery-removed-note hidden>{{ __('admin.form.gallery.removed_note') }}</span>
@unless ($saved)
    <span class="yq-gallery__note yq-gallery__note--new">{{ __('admin.form.gallery.new_note') }}</span>
@endunless
<div class="yq-gallery__controls">
    <button type="button" class="btn btn-sm btn-outline-dark mb-0 yq-icon-btn" data-gallery-move="-1"
        aria-label="{{ __('admin.form.gallery.move_earlier') }}">
        <x-icon name="chevron-left" size="16" flip-rtl />
    </button>
    <button type="button" class="btn btn-sm btn-outline-dark mb-0 yq-icon-btn" data-gallery-move="1"
        aria-label="{{ __('admin.form.gallery.move_later') }}">
        <x-icon name="chevron-right" size="16" flip-rtl />
    </button>
    <button type="button" class="btn btn-sm btn-outline-danger mb-0 yq-icon-btn yq-gallery__remove" data-gallery-remove
        aria-label="{{ __('admin.form.gallery.remove') }}">
        <x-icon name="trash" size="16" class="yq-gallery__icon-remove" />
        <x-icon name="rotate-ccw" size="16" class="yq-gallery__icon-keep" />
    </button>
</div>
