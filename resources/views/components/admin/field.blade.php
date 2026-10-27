{{--
    A labelled, non-translated input. Extra attributes (min, max, inputmode, ...) go on the input:
    <x-admin.field name="sort_order" type="number" min="0" :label="__('...')" :value="$model->sort_order" />
--}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'help' => null])

<div class="mb-3">
    <label class="form-label" for="field-{{ $name }}">
        {{ $label }}
        @if ($required)
            <span class="yq-required" aria-hidden="true">*</span>
            <span class="visually-hidden">({{ __('admin.form.required') }})</span>
        @endif
    </label>
    <input {{ $attributes->class(['form-control']) }} type="{{ $type }}" id="field-{{ $name }}" name="{{ $name }}"
        value="{{ $value }}" @if ($required) aria-required="true" @endif
        @if ($help) aria-describedby="help-{{ $name }}" @endif>
    @if ($help)
        <div class="form-text" id="help-{{ $name }}">{{ $help }}</div>
    @endif
</div>
