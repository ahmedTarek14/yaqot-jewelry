{{--
    The public filter form (GET). Shared by the catalog, the category, collection and brand pages
    (which pass taxonomies without their own filter) and the bullion page ($filters->bullion).
    An attribute with no choices (e.g. every gold colour deactivated) is left out.
    Expects: $filters (CatalogFilters), $taxonomies and $attributes (CatalogOptions), $action (the page URL).
    front.js leaves empty fields out of the URL and submits on a sort change.
--}}
<form id="catalog-filters" class="yq-filter-form" method="get" action="{{ $action }}" data-filter-form>
    <div class="yq-filter-form__group">
        <label for="filter-q" class="form-label">{{ __('product::catalog.filters.search') }}</label>
        <input type="search" class="form-control" id="filter-q" name="q" value="{{ $filters->value('q') }}"
            maxlength="{{ config('catalog.front.search_max_length') }}"
            placeholder="{{ __('product::catalog.filters.search_placeholder') }}">
    </div>

    @foreach ($taxonomies as $name => $models)
        @if ($models->isNotEmpty())
            <div class="yq-filter-form__group">
                {{-- On the bullion page a brand is the maker of the bar or coin. --}}
                <label for="filter-{{ $name }}" class="form-label">{{ __('product::catalog.filters.'.($filters->bullion && $name === 'brand' ? 'manufacturer' : $name)) }}</label>
                <select class="form-select" id="filter-{{ $name }}" name="{{ $name }}">
                    <option value="">{{ __('product::catalog.filters.all') }}</option>
                    @foreach ($models as $model)
                        <option value="{{ $model->slug }}" @selected($filters->value($name) === $model->slug)>{{ $model->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    @endforeach

    @foreach ($attributes as $attribute => $choices)
        @continue($choices === [])
        <fieldset class="yq-filter-form__group">
            <legend class="form-label">{{ __("product::catalog.filters.$attribute") }}</legend>
            <div class="yq-chips">
                <input type="radio" class="btn-check" name="{{ $attribute }}" id="filter-{{ $attribute }}-all" value=""
                    @checked($filters->value($attribute) === null)>
                <label class="yq-chip" for="filter-{{ $attribute }}-all">{{ __('product::catalog.filters.all') }}</label>
                @foreach ($choices as $value => $label)
                    <input type="radio" class="btn-check" name="{{ $attribute }}" id="filter-{{ $attribute }}-{{ $value }}"
                        value="{{ $value }}" @checked($filters->value($attribute) === (string) $value)>
                    <label class="yq-chip" for="filter-{{ $attribute }}-{{ $value }}">{{ $label }}</label>
                @endforeach
            </div>
        </fieldset>
    @endforeach

    <fieldset class="yq-filter-form__group">
        <legend class="form-label">{{ __('product::catalog.filters.weight') }}</legend>
        <div class="yq-range">
            <div>
                <label for="filter-weight-min" class="yq-range__label">{{ __('product::catalog.filters.weight_min') }}</label>
                <input type="number" class="form-control" id="filter-weight-min" name="weight_min" min="0" max="99999.999"
                    step="0.001" inputmode="decimal" value="{{ $filters->value('weight_min') }}">
            </div>
            <div>
                <label for="filter-weight-max" class="yq-range__label">{{ __('product::catalog.filters.weight_max') }}</label>
                <input type="number" class="form-control" id="filter-weight-max" name="weight_max" min="0" max="99999.999"
                    step="0.001" inputmode="decimal" value="{{ $filters->value('weight_max') }}">
            </div>
        </div>
    </fieldset>

    <div class="yq-filter-form__actions">
        <button type="submit" class="btn btn-gold">{{ __('product::catalog.filters.apply') }}</button>
        @if ($filters->isFiltered())
            <a href="{{ $filters->clearUrl($action) }}" class="yq-underline">{{ __('product::catalog.filters.clear') }}</a>
        @endif
    </div>
</form>
