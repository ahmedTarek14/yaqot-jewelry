<div class="yq-locale-tabs">
    <ul class="nav nav-tabs" role="tablist" aria-label="{{ __('admin.form.languages') }}">
        @foreach ($tabs as $tab)
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link @if ($loop->first) active @endif" role="tab"
                    id="{{ $id }}-tab-{{ $tab['code'] }}" data-bs-toggle="tab"
                    data-bs-target="#{{ $id }}-{{ $tab['code'] }}" aria-controls="{{ $id }}-{{ $tab['code'] }}"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                    <span lang="{{ $tab['code'] }}">{{ $tab['native'] }}</span>
                    <span class="yq-tab-error" hidden>
                        <span class="yq-tab-error__mark" aria-hidden="true">!</span>
                        <span class="visually-hidden">{{ __('admin.form.tab_has_errors') }}</span>
                    </span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content pt-3">
        @foreach ($tabs as $tab)
            <div class="tab-pane fade @if ($loop->first) show active @endif" id="{{ $id }}-{{ $tab['code'] }}"
                role="tabpanel" aria-labelledby="{{ $id }}-tab-{{ $tab['code'] }}" tabindex="0">
                @foreach ($tab['inputs'] as $input)
                    <div class="mb-3">
                        <label class="form-label" for="field-{{ $input['name'] }}">
                            {{ $input['label'] }}
                            @if ($input['required'])
                                <span class="yq-required" aria-hidden="true">*</span>
                                <span class="visually-hidden">({{ __('admin.form.required') }})</span>
                            @endif
                        </label>
                        @if ($input['type'] === 'textarea')
                            <textarea class="form-control" id="field-{{ $input['name'] }}" name="{{ $input['name'] }}"
                                rows="{{ $input['rows'] }}" lang="{{ $tab['code'] }}" dir="{{ $tab['dir'] }}"
                                @if ($input['maxlength']) maxlength="{{ $input['maxlength'] }}" @endif
                                @if ($input['required']) aria-required="true" @endif
                                @if ($input['help']) aria-describedby="help-{{ $input['name'] }}" @endif>{{ $input['value'] }}</textarea>
                        @else
                            <input type="text" class="form-control" id="field-{{ $input['name'] }}" name="{{ $input['name'] }}"
                                value="{{ $input['value'] }}" lang="{{ $tab['code'] }}" dir="{{ $tab['dir'] }}"
                                @if ($input['maxlength']) maxlength="{{ $input['maxlength'] }}" @endif
                                @if ($input['required']) aria-required="true" @endif
                                @if ($input['help']) aria-describedby="help-{{ $input['name'] }}" @endif>
                        @endif
                        @if ($input['help'])
                            <div class="form-text" id="help-{{ $input['name'] }}">{{ $input['help'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
