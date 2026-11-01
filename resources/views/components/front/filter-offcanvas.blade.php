{{--
    Filters: an offcanvas panel below 992px (reusing .yq-offcanvas, Motion: "Offcanvas"),
    an ordinary sidebar from 992px (Bootstrap's .offcanvas-lg).
    Open it with a button: data-bs-toggle="offcanvas" data-bs-target="#{id}" aria-controls="{id}".

    <x-front.filter-offcanvas id="catalog-filters-panel" :title="__('...')" :close-label="__('...')">
        <form>...</form>
    </x-front.filter-offcanvas>
--}}
@props(['id', 'title', 'closeLabel'])

<div {{ $attributes->class(['offcanvas-lg', 'offcanvas-end', 'yq-offcanvas', 'yq-filters']) }} tabindex="-1"
    id="{{ $id }}" aria-labelledby="{{ $id }}-title">
    <div class="offcanvas-header">
        <h2 class="yq-filters__title" id="{{ $id }}-title">{{ $title }}</h2>
        <button type="button" class="yq-icon-btn d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#{{ $id }}"
            aria-label="{{ $closeLabel }}">
            <x-icon name="x" />
        </button>
    </div>
    <div class="offcanvas-body">
        {{ $slot }}
    </div>
</div>
