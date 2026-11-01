{{--
    A category or collection tile: framed image (or a gold placeholder) with the name below.
    Put tiles in a <ul data-reveal-group> with <li data-reveal> (Motion: "Staggered group").

    <x-front.tile :url="$url" :title="$category->name" :image="$category->publicImageUrl()" />
    <x-front.tile ... ratio="landscape" />   square (default) or landscape
    <x-front.tile ... :text="$description" />  an optional short line below the name
--}}
@props(['url', 'title', 'image' => null, 'text' => null, 'ratio' => 'square'])

<a href="{{ $url }}" {{ $attributes->class(['yq-tile', 'yq-frame-link']) }}>
    <span @class(['yq-frame', 'yq-frame--square' => $ratio === 'square', 'yq-frame--landscape' => $ratio === 'landscape'])>
        @if ($image)
            <img src="{{ $image }}" alt="" width="800" height="{{ $ratio === 'landscape' ? 600 : 800 }}" loading="lazy" decoding="async">
        @else
            <span class="yq-frame__placeholder"><x-icon name="gem" size="32" /></span>
        @endif
    </span>
    <span class="yq-tile__caption">
        <span class="yq-tile__title">{{ $title }}</span>
        <x-icon name="arrow-right" size="16" class="yq-tile__arrow" flip-rtl />
    </span>
    @if ($text)
        <span class="yq-tile__text">{{ $text }}</span>
    @endif
</a>
