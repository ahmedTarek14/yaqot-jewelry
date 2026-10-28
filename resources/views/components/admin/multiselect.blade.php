<div class="mb-3 yq-multiselect" data-multiselect data-field-errors="{{ $name }}" tabindex="-1" role="group"
    aria-labelledby="label-{{ $name }}" @if ($help) aria-describedby="help-{{ $name }}" @endif>
    <span class="form-label d-block" id="label-{{ $name }}">{{ $label }}</span>

    @if ($choices === [])
        <p class="text-sm text-muted mb-0">{{ $empty ?? __('admin.form.multiselect.empty') }}</p>
    @else
        <input type="search" class="form-control form-control-sm mb-2" data-multiselect-search hidden
            aria-label="{{ __('admin.form.multiselect.search', ['field' => $label]) }}"
            placeholder="{{ __('admin.form.multiselect.search', ['field' => $label]) }}" aria-controls="options-{{ $name }}">

        <div class="yq-multiselect__list" id="options-{{ $name }}">
            @foreach ($choices as $choice)
                <div class="form-check" data-multiselect-option>
                    <input class="form-check-input" type="checkbox" name="{{ $name }}[]" value="{{ $choice['value'] }}"
                        id="field-{{ $name }}-{{ $choice['value'] }}" @checked($choice['checked'])>
                    <label class="form-check-label" for="field-{{ $name }}-{{ $choice['value'] }}">{{ $choice['label'] }}</label>
                </div>
            @endforeach
            <p class="text-sm text-muted mb-0 py-1" data-multiselect-none hidden>{{ __('admin.form.multiselect.none_match') }}</p>
        </div>

        <div class="form-text" aria-live="polite" data-multiselect-count>
            {{ __('admin.form.multiselect.selected', ['count' => $selectedCount]) }}
        </div>
    @endif

    @if ($help)
        <div class="form-text" id="help-{{ $name }}">{{ $help }}</div>
    @endif
</div>
