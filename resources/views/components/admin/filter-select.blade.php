{{-- A filter for <x-admin.listing-toolbar>. $options: value => label; the empty option means "all". --}}
@props(['name', 'label', 'options' => [], 'value' => null])

<div class="yq-toolbar__filter">
    <label class="form-label text-xs mb-1" for="filter-{{ $name }}">{{ $label }}</label>
    <select class="form-select form-select-sm" id="filter-{{ $name }}" name="{{ $name }}">
        <option value="">{{ __('admin.listing.any') }}</option>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
