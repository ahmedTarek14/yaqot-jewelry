{{--
    Search and filters for an index page (GET form). Filters go in the slot:
    <x-admin.listing-toolbar :listing="$listing" :placeholder="__('...')">
        <x-admin.filter-select name="status" ... />
    </x-admin.listing-toolbar>
    The current sort is kept; the page number resets.
--}}
@props(['listing', 'placeholder' => null])

<form method="GET" action="{{ $listing->resetUrl() }}" class="yq-toolbar" role="search" aria-label="{{ __('admin.listing.filters') }}">
    <div class="yq-toolbar__search">
        <label class="visually-hidden" for="listing-q">{{ __('admin.actions.search') }}</label>
        <div class="input-group">
            <span class="input-group-text"><x-icon name="search" size="16" /></span>
            <input type="search" class="form-control" id="listing-q" name="q" value="{{ $listing->search }}"
                maxlength="100" placeholder="{{ $placeholder ?? __('admin.listing.search_placeholder') }}">
        </div>
    </div>

    {{ $slot }}

    @foreach ($listing->sortParameters() as $parameter => $parameterValue)
        <input type="hidden" name="{{ $parameter }}" value="{{ $parameterValue }}">
    @endforeach

    <div class="yq-toolbar__actions">
        <button type="submit" class="btn btn-dark btn-sm mb-0">{{ __('admin.actions.search') }}</button>
        @if ($listing->isFiltered())
            <a href="{{ $listing->resetUrl() }}" class="btn btn-outline-secondary btn-sm mb-0">{{ __('admin.actions.clear') }}</a>
        @endif
    </div>
</form>
