{{-- A sortable column header. Only columns in the controller's allowlist should use it. --}}
@props(['listing', 'column', 'label'])

<th scope="col" aria-sort="{{ $listing->ariaSort($column) }}" {{ $attributes->class(['yq-sortable']) }}>
    <a href="{{ $listing->sortUrl($column) }}" class="yq-sort-link">
        {{ $label }}
        @if ($listing->sort === $column)
            <x-icon :name="$listing->direction === 'asc' ? 'chevron-up' : 'chevron-down'" size="14" />
        @else
            <x-icon name="chevrons-up-down" size="14" class="yq-sort-link__idle" />
        @endif
    </a>
</th>
