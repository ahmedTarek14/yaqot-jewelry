{{-- A dashboard figure: a label, a number and an optional link to the matching index page. --}}
@props(['label', 'value', 'icon' => null, 'url' => null, 'linkLabel' => null])

<div {{ $attributes->class(['card yq-stat h-100']) }}>
    <div class="card-body d-flex align-items-start gap-3">
        @if ($icon)
            <span class="yq-stat__icon" aria-hidden="true"><x-icon :name="$icon" size="22" /></span>
        @endif
        <div class="flex-grow-1">
            <p class="yq-stat__label mb-1">{{ $label }}</p>
            <p class="yq-stat__value mb-0">{{ $value }}</p>
            @if ($url)
                <a href="{{ $url }}" class="yq-stat__link text-sm">{{ $linkLabel ?? __('admin.actions.view_all') }}</a>
            @endif
        </div>
    </div>
</div>
