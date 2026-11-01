@extends('layouts.front.master')

@section('content')
    {{-- Hero: title, subtitle, image and button from Settings. data-enter: load sequence (Motion). --}}
    <section @class(['yq-hero', 'yq-hero--with-image' => $hero['image']]) aria-labelledby="yq-hero-title">
        <div class="container yq-hero__grid">
            <div class="yq-hero__content" data-enter-group>
                <p class="yq-eyebrow" data-enter>{{ __('home::home.hero.eyebrow') }}</p>
                <div class="yq-ornament" aria-hidden="true" data-enter><span></span></div>
                <h1 class="yq-hero__title" id="yq-hero-title" data-enter>{{ $hero['title'] }}</h1>
                @if ($hero['subtitle'])
                    <p class="yq-hero__subtitle" data-enter>{{ $hero['subtitle'] }}</p>
                @endif
                <div data-enter>
                    <a href="{{ $hero['ctaUrl'] }}" class="btn btn-gold">{{ $hero['ctaLabel'] }}</a>
                </div>
            </div>
            @if ($hero['image'])
                <div class="yq-hero__media" data-enter>
                    <span class="yq-frame">
                        <img src="{{ $hero['image'] }}" alt="" width="800" height="1000" fetchpriority="high">
                    </span>
                </div>
            @endif
        </div>
    </section>

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
                <ul class="yq-brands__list" data-reveal-group>
                    @foreach ($brands as $brand)
                        <li data-reveal>
                            <a href="{{ route('front.brands.show', $brand->slug) }}" class="yq-brands__link">{{ $brand->name }}</a>
                        </li>
                    @endforeach
                </ul>
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

    @if ($about)
        <section class="yq-section yq-section--light" aria-labelledby="home-about">
            <div class="container yq-about-intro">
                <x-front.section-heading id="home-about" :title="__('home::home.sections.about')" />
                <p class="yq-about-intro__text yq-text" data-reveal>{{ $about }}</p>
                <p data-reveal>
                    <a href="{{ $aboutUrl }}" class="btn btn-outline-gold">{{ __('home::home.sections.about_more') }}</a>
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
