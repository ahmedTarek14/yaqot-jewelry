{{-- Index card header: title, optional total, and actions (slot). --}}
@props(['title', 'count' => null])

<div class="card-header pb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
    <h2 class="h6 mb-0 d-flex align-items-center gap-2">
        {{ $title }}
        @if ($count !== null)
            <span class="yq-count">{{ $count }}</span>
        @endif
    </h2>
    @if ($slot->isNotEmpty())
        <div class="d-flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</div>
