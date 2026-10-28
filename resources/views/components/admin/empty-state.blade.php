{{--
    Shown instead of the table: no records at all, or none matching the search/filters.
    $listing is optional (e.g. the dashboard, which has no search or filters).
--}}
@props(['listing' => null, 'message' => null, 'createUrl' => null, 'createLabel' => null])

<div class="yq-empty" role="status">
    <span class="yq-empty__icon" aria-hidden="true"><x-icon name="inbox" size="28" /></span>
    @if ($listing?->isFiltered())
        <p class="mb-3">{{ __('admin.listing.empty_filtered') }}</p>
        <a href="{{ $listing->resetUrl() }}" class="btn btn-outline-dark btn-sm mb-0">{{ __('admin.actions.clear') }}</a>
    @else
        <p class="mb-3">{{ $message ?? __('admin.listing.empty') }}</p>
        @if ($createUrl)
            <a href="{{ $createUrl }}" class="btn btn-primary btn-sm mb-0 d-inline-flex align-items-center gap-1">
                <x-icon name="plus" size="16" /> {{ $createLabel ?? __('admin.actions.create') }}
            </a>
        @endif
    @endif
</div>
