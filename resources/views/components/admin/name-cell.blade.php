@if ($url)
    <a href="{{ $url }}" class="yq-table__title">{{ $primary ?? __('admin.listing.missing_translation') }}</a>
@else
    <span class="yq-table__title">{{ $primary ?? __('admin.listing.missing_translation') }}</span>
@endif
@foreach ($others as $other)
    <span class="d-block text-xs text-muted" lang="{{ $other['code'] }}">{{ $other['name'] ?? __('admin.listing.missing_translation') }}</span>
@endforeach
