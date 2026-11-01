{{--
    A section heading with the gold ornament; eyebrow, ornament and title reveal in turn
    (Motion: "Ornament draw-in" in a staggered group).

    <x-front.section-heading id="featured-title" :title="__('...')" />
    <x-front.section-heading ... :eyebrow="__('...')" :link="$url" :link-label="__('front.actions.view_all')" />
    Give the <section> aria-labelledby="{id}".
--}}
@props(['title', 'id' => null, 'eyebrow' => null, 'link' => null, 'linkLabel' => null])

<div {{ $attributes->class('yq-section-heading') }} data-reveal-group>
    @if ($eyebrow)
        <p class="yq-eyebrow" data-reveal>{{ $eyebrow }}</p>
    @endif
    <div class="yq-ornament" aria-hidden="true" data-reveal><span></span></div>
    <h2 class="yq-section-heading__title" @if ($id) id="{{ $id }}" @endif data-reveal>{{ $title }}</h2>
    @if ($link)
        <p class="yq-section-heading__link" data-reveal>
            <a href="{{ $link }}" class="yq-underline">{{ $linkLabel ?? __('front.actions.view_all') }}</a>
            <x-icon name="arrow-right" size="16" flip-rtl />
        </p>
    @endif
</div>
