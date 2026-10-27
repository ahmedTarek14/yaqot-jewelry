{{-- A boolean form field. The hidden 0 makes an unchecked switch submit a value too. --}}
@props(['name', 'label', 'checked' => false, 'help' => null])

<div class="mb-3">
    <input type="hidden" name="{{ $name }}" value="0">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch" id="field-{{ $name }}" name="{{ $name }}"
            value="1" @checked($checked) @if ($help) aria-describedby="help-{{ $name }}" @endif>
        <label class="form-check-label" for="field-{{ $name }}">{{ $label }}</label>
    </div>
    @if ($help)
        <div class="form-text" id="help-{{ $name }}">{{ $help }}</div>
    @endif
</div>
