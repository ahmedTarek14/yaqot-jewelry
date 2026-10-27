{{--
    Single image upload with a preview (admin.js shows the chosen file before saving).
    With an existing image, a "remove" checkbox submits remove_{name}=1.
    $type is the upload type (config('catalog.images.min_pixels') key), the same one
    the Form Request passes to ImageUploader::rules(), so the hint matches the rule.
--}}
@props(['type', 'name' => 'image', 'label' => null, 'url' => null])

<div class="yq-image-input" data-image-input>
    <div class="yq-image-input__preview">
        <img @if ($url) src="{{ $url }}" @endif alt="{{ __('admin.form.image.current') }}" data-image-preview
            data-saved-src="{{ $url }}" data-saved-alt="{{ __('admin.form.image.current') }}"
            data-preview-alt="{{ __('admin.form.image.preview') }}" @if (! $url) hidden @endif>
        <span class="yq-image-input__empty" data-image-empty @if ($url) hidden @endif>
            <x-icon name="image" size="28" />
            <span>{{ __('admin.form.image.none') }}</span>
        </span>
    </div>

    <div class="mt-3">
        <label class="form-label" for="field-{{ $name }}">{{ $label ?? __('admin.form.image.label') }}</label>
        <input type="file" class="form-control" id="field-{{ $name }}" name="{{ $name }}"
            accept="image/jpeg,image/png,image/webp" aria-describedby="help-{{ $name }}" data-image-file>
        <div class="form-text" id="help-{{ $name }}">
            {{ __('admin.form.image.hint', [
                'size' => config('catalog.images.max_kilobytes') / 1024,
                'min' => \App\Support\ImageUploader::minPixels($type),
                'max' => config('catalog.images.max_pixels'),
                'edge' => config('catalog.images.max_edge'),
            ]) }}
        </div>
    </div>

    @if ($url)
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="field-remove_{{ $name }}" name="remove_{{ $name }}"
                value="1" data-image-remove>
            <label class="form-check-label" for="field-remove_{{ $name }}">{{ __('admin.form.image.remove') }}</label>
        </div>
    @endif
</div>
