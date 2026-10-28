{{--
    A labelled single-choice select. $options: value => label.
    $placeholder adds an empty first option (e.g. "None"); without it a value is always chosen.
    <x-admin.select name="karat" :label="__('...')" :options="$karats" :value="$product->karat" :placeholder="__('...')" />
--}}
@props(['name', 'label', 'options' => [], 'value' => null, 'required' => false, 'help' => null, 'placeholder' => null])

<div class="mb-3">
    <label class="form-label" for="field-{{ $name }}">
        {{ $label }}
        @if ($required)
            <span class="yq-required" aria-hidden="true">*</span>
            <span class="visually-hidden">({{ __('admin.form.required') }})</span>
        @endif
    </label>
    <select {{ $attributes->class(['form-select']) }} id="field-{{ $name }}" name="{{ $name }}"
        @if ($required) aria-required="true" @endif @if ($help) aria-describedby="help-{{ $name }}" @endif>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($value !== null && (string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($help)
        <div class="form-text" id="help-{{ $name }}">{{ $help }}</div>
    @endif
</div>
