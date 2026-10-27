<a href="{{ $url }}" class="yq-table__title">{{ $primary ?? __('admin.listing.missing_translation') }}</a>
@foreach ($others as $other)
    <span class="d-block text-xs text-muted" lang="{{ $other['code'] }}">{{ $other['name'] ?? __('admin.listing.missing_translation') }}</span>
@endforeach
