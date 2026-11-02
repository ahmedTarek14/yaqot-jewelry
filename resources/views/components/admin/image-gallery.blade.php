{{--
    Several images with reordering (drag, or the earlier/later buttons), single-image removal
    and new uploads. The first image is the primary one. Nothing is saved until the form is.
    $images: the saved images in order, each with an id and a url (e.g. $product->images).
    The form submits, for name="images":
      images[]           the new files, in the order they appear in the gallery
      images_order[]     the final order as tokens: "id:{id}" (saved) or "new:{n}" (images[n])
      remove_images[]    ids of saved images to delete
    $type is the upload type passed to ImageUploader::rules() (sets the size hint).
    Validation errors for images, images.N, images_order.N and remove_images.N show under the gallery.
--}}
@props(['type', 'images' => [], 'name' => 'images', 'max' => null])

<div class="yq-gallery" data-gallery data-field-errors="{{ $name }} {{ $name }}_order remove_{{ $name }}" tabindex="-1"
    role="group" aria-labelledby="label-{{ $name }}" aria-describedby="help-{{ $name }}">
    <span class="form-label d-block" id="label-{{ $name }}">{{ __('admin.form.gallery.label') }}</span>

    <ol class="yq-gallery__list" data-gallery-list>
        @foreach ($images as $image)
            <li class="yq-gallery__item" draggable="true" data-gallery-item>
                <input type="hidden" name="{{ $name }}_order[]" value="id:{{ $image->id }}" data-gallery-token>
                <input type="hidden" name="remove_{{ $name }}[]" value="{{ $image->id }}" disabled data-gallery-remove-input>
                @include('components.admin.partials.gallery-tile', ['src' => $image->url, 'saved' => true])
            </li>
        @endforeach
    </ol>

    <p class="text-sm text-muted yq-gallery__empty" data-gallery-empty @if (count($images)) hidden @endif>
        {{ __('admin.form.gallery.empty') }}
    </p>

    <template data-gallery-template>
        <li class="yq-gallery__item is-new" draggable="true" data-gallery-item data-gallery-new>
            <input type="hidden" name="{{ $name }}_order[]" value="" data-gallery-token>
            @include('components.admin.partials.gallery-tile', ['src' => null, 'saved' => false])
        </li>
    </template>

    <div class="mt-3">
        <label class="form-label" for="field-{{ $name }}">{{ __('admin.form.gallery.add') }}</label>
        <input type="file" class="form-control" id="field-{{ $name }}" name="{{ $name }}[]" multiple
            accept="image/jpeg,image/png,image/webp" data-gallery-file>
        <div class="form-text" id="help-{{ $name }}">
            {{ __('admin.form.image.hint', [
                'size' => config('catalog.images.max_kilobytes') / 1024,
                'min' => \App\Support\ImageUploader::minPixels($type),
                'max' => config('catalog.images.max_pixels'),
                'edge' => config('catalog.images.max_edge'),
            ]) }}
            {{ __('admin.form.gallery.help', ['max' => $max ?? config('catalog.images.max_per_product')]) }}
            {{ __('admin.limits.server', ['post' => $uploadLimits['postLabel'], 'file' => $uploadLimits['fileLabel']]) }}
        </div>
    </div>

    <p class="visually-hidden" aria-live="polite" data-gallery-status></p>
</div>
