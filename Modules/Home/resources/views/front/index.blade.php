@extends('layouts.front.master')

@section('header_class', 'yq-header--overlay')

@section('content')
    <x-front.json-ld :data="$organization" />

    {{-- Hero slider (Phase 5b). The silk background is shared and still; only the slides change.
         A Bootstrap crossfade carousel: without JS the first slide shows and the controls stay hidden.
         Each slide's text plays the data-enter sequence whenever the slide is shown (Motion). --}}
    <section class="yq-hero" aria-label="{{ __('home::home.hero.label') }}" data-hero>
        <picture class="yq-hero__bg" aria-hidden="true">
            {{-- Portrait phones get the small file; the 4K one is only for very wide screens. --}}
            <source media="(max-width: 767.98px) and (orientation: portrait)" srcset="{{ versioned_asset('assets/images/hero/silk-mobile.webp') }}" type="image/webp">
            <source media="(min-width: 2200px)" srcset="{{ versioned_asset('assets/images/hero/silk-4k.webp') }}" type="image/webp">
            <img src="{{ versioned_asset('assets/images/hero/silk-2k.webp') }}" alt="" width="2560" height="1440" decoding="async" fetchpriority="high">
        </picture>
        <div class="yq-hero__light" aria-hidden="true"></div>
        <div class="yq-hero__particles" aria-hidden="true">
            @for ($i = 0; $i < 12; $i++)
                <i></i>
            @endfor
        </div>

        <div id="yq-hero-carousel" @class(['carousel slide carousel-fade yq-hero__carousel', 'is-single' => ! $multiple])
            @if ($multiple) data-hero-carousel role="region" aria-roledescription="carousel" aria-label="{{ __('home::home.hero.slideshow') }}" @endif>
            <div class="carousel-inner">
                @foreach ($slides as $slide)
                    <div @class(['carousel-item yq-hero__slide', 'active' => $loop->first])
                        @if ($multiple) role="group" aria-roledescription="slide" aria-label="{{ __('home::home.hero.slide_of', ['current' => $loop->iteration, 'total' => $loop->count]) }}" @endif>
                        <div class="container yq-hero__grid">
                            <div class="yq-hero__content" data-enter-group>
                                <p class="yq-eyebrow yq-hero__eyebrow" lang="en" dir="ltr" data-enter>{{ __('home::home.hero.eyebrow') }}</p>
                                <div class="yq-ornament" aria-hidden="true" data-enter><span></span></div>
                                @if ($loop->first)
                                    <h1 class="yq-hero__title" data-enter>{{ $slide['title'] }}</h1>
                                @else
                                    <h2 class="yq-hero__title" data-enter>{{ $slide['title'] }}</h2>
                                @endif
                                <p class="yq-hero__subtitle" data-enter>{{ $slide['subtitle'] }}</p>
                                <div data-enter>
                                    <a href="{{ $slide['ctaUrl'] }}" class="btn btn-gold yq-hero__cta">{{ $slide['ctaLabel'] }}</a>
                                </div>
                            </div>
                            <div class="yq-hero__media">
                                <div class="yq-hero__float">
                                    {{-- Only the first slide's image is eager (LCP); front.js warms the others after load. --}}
                                    <img src="{{ $slide['src'] }}" srcset="{{ $slide['srcset'] }}" sizes="(min-width: 768px) 44vw, 90vw"
                                        alt="{{ $slide['alt'] }}" width="{{ $slide['width'] }}" height="{{ $slide['height'] }}"
                                        decoding="async" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($multiple)
                <div class="container yq-hero__controls">
                    <button type="button" class="yq-hero__toggle" data-hero-toggle
                        data-label-pause="{{ __('home::home.hero.pause') }}" data-label-play="{{ __('home::home.hero.play') }}"
                        aria-label="{{ __('home::home.hero.pause') }}">
                        <x-icon name="pause" size="16" class="yq-hero__icon-pause" />
                        <x-icon name="play" size="16" class="yq-hero__icon-play" />
                    </button>
                    <div class="carousel-indicators yq-hero__indicators">
                        @foreach ($slides as $slide)
                            <button type="button" data-bs-target="#yq-hero-carousel" data-bs-slide-to="{{ $loop->index }}"
                                @class(['active' => $loop->first]) @if ($loop->first) aria-current="true" @endif
                                aria-label="{{ __('home::home.hero.show_slide', ['number' => $loop->iteration]) }}"></button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <a href="#after-hero" class="yq-hero__scroll" aria-label="{{ __('home::home.hero.scroll') }}">
            <span class="yq-hero__scroll-line" aria-hidden="true"></span>
            <span class="yq-hero__scroll-mouse" aria-hidden="true"><span></span></span>
            <x-icon name="chevron-down" size="18" />
        </a>
    </section>
    <div id="after-hero" class="yq-hero__end"></div>

    @if ($categories->isNotEmpty())
        <section class="yq-section" aria-labelledby="home-categories">
            <div class="container">
                <x-front.section-heading id="home-categories" :title="__('home::home.sections.categories')"
                    :link="$catalogUrl" />
                <ul class="yq-tile-grid" data-reveal-group>
                    @foreach ($categories as $category)
                        <li data-reveal>
                            <x-front.tile :url="route('front.categories.show', $category->slug)"
                                :title="$category->name" :image="$category->publicImageUrl()" />
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($featured->isNotEmpty())
        <section class="yq-section yq-section--alt" aria-labelledby="home-featured">
            <div class="container">
                <x-front.section-heading id="home-featured" :title="__('home::home.sections.featured')"
                    :link="$catalogUrl" />
                <x-front.product-grid :products="$featured" />
            </div>
        </section>
    @endif

    @if ($collections->isNotEmpty())
        <section class="yq-section" aria-labelledby="home-collections">
            <div class="container">
                <x-front.section-heading id="home-collections" :title="__('home::home.sections.collections')" />
                <ul @class(['yq-collection-grid', 'has-prominent' => $prominentCollection]) data-reveal-group>
                    @foreach ($collections as $collection)
                        <li data-reveal @class(['is-prominent' => $prominentCollection && $loop->first])>
                            <x-front.tile :url="route('front.collections.show', $collection->slug)"
                                :title="$collection->name" :image="$collection->publicImageUrl()" ratio="landscape" />
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($brands->isNotEmpty())
        <section class="yq-brands" aria-labelledby="home-brands">
            <div class="container">
                <h2 class="yq-brands__title" id="home-brands">{{ __('home::home.sections.brands') }}</h2>
            </div>
            {{-- Marquee (Motion): two equal halves of the list, the track slides by one half, endlessly.
                 Only the first list is in the accessibility tree; every copy is aria-hidden with unfocusable links. --}}
            <div class="yq-marquee" style="--yq-marquee-items: {{ $brands->count() * $brandRepeat }}">
                <div class="yq-marquee__track">
                    @for ($half = 0; $half < 2; $half++)
                        <div class="yq-marquee__half" @if ($half) aria-hidden="true" @endif>
                            @for ($copy = 0; $copy < $brandRepeat; $copy++)
                                <ul class="yq-marquee__list" @if ($half || $copy) aria-hidden="true" @else aria-labelledby="home-brands" @endif>
                                    @foreach ($brands as $brand)
                                        <li>
                                            <a href="{{ route('front.brands.show', $brand->slug) }}" class="yq-brands__link"{!! $half || $copy ? ' tabindex="-1"' : '' !!}>{{ $brand->name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endfor
                        </div>
                    @endfor
                </div>
            </div>
        </section>
    @endif

    @if ($latest->isNotEmpty())
        <section class="yq-section" aria-labelledby="home-latest">
            <div class="container">
                <x-front.section-heading id="home-latest" :title="__('home::home.sections.latest')" :link="$catalogUrl" />
                <x-front.product-grid :products="$latest" />
            </div>
        </section>
    @endif

    @if ($bullionUrl && $bullion->isNotEmpty())
        <section class="yq-section yq-section--alt" aria-labelledby="home-bullion">
            <div class="container">
                <x-front.section-heading id="home-bullion" :title="__('home::home.sections.bullion')"
                    :link="$bullionUrl" />
                <x-front.product-grid :products="$bullion" />
            </div>
        </section>
    @endif

    @if ($about)
        <section class="yq-section yq-section--light" aria-labelledby="home-about">
            <div class="container yq-about-intro">
                <x-front.section-heading id="home-about" :title="__('home::home.sections.about')" />
                <p class="yq-about-intro__text yq-text" data-reveal>{{ $about }}</p>
                <p data-reveal>
                    <a href="{{ $aboutUrl }}" class="btn btn-outline-gold">{{ __('home::home.sections.about_more') }}<span class="visually-hidden">: {{ __('home::home.sections.about') }}</span></a>
                </p>
            </div>
        </section>
    @endif

    @if ($contact['whatsappUrl'] || $contact['phone'])
        <section class="yq-section" aria-labelledby="home-contact">
            <div class="container">
                <div class="yq-cta" data-reveal-group>
                    <div class="yq-ornament" aria-hidden="true" data-reveal><span></span></div>
                    <h2 class="yq-cta__title" id="home-contact" data-reveal>{{ __('home::home.sections.contact') }}</h2>
                    <p class="yq-cta__text" data-reveal>{{ __('home::home.sections.contact_text') }}</p>
                    <div class="yq-cta__actions" data-reveal>
                        @if ($contact['whatsappUrl'])
                            <a href="{{ $contact['whatsappUrl'] }}" class="btn btn-gold" target="_blank" rel="noopener">
                                <x-icon name="message-circle" size="18" />
                                {{ __('front.contact.whatsapp_cta') }}
                                <span class="visually-hidden">{{ __('front.contact.opens_new_tab') }}</span>
                            </a>
                        @endif
                        @if ($contact['phone'])
                            <a href="{{ $contact['phone']['url'] }}" class="btn btn-outline-gold"
                                aria-label="{{ __('front.contact.phone', ['number' => $contact['phone']['label']]) }}">
                                <x-icon name="phone" size="18" />
                                <span dir="ltr">{{ $contact['phone']['label'] }}</span>
                            </a>
                        @endif
                        <a href="{{ $contact['pageUrl'] }}" class="yq-underline">{{ __('front.nav.contact') }}</a>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
