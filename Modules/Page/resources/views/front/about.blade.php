@extends('layouts.front.master')

@section('title', __('page::about.title'))

@section('content')
    <div class="yq-page-header">
        <div class="container">
            <x-front.breadcrumbs :items="[['label' => __('page::about.title')]]" />
            <h1 class="yq-page-header__title">{{ __('page::about.title') }}</h1>
        </div>
    </div>

    <section class="yq-section" aria-label="{{ __('page::about.title') }}">
        <div class="container">
            @if ($paragraphs)
                <div class="yq-prose" data-reveal-group>
                    @foreach ($paragraphs as $paragraph)
                        <p class="yq-prose__text yq-text" data-reveal>{{ $paragraph }}</p>
                    @endforeach
                </div>
            @else
                <x-front.empty-state icon="gem" :title="__('page::about.empty_title')" :text="__('page::about.empty_text')" />
            @endif
        </div>
    </section>

    @if ($whatsappUrl || $phone)
        <section class="yq-section yq-section--alt" aria-labelledby="about-contact">
            <div class="container">
                <div class="yq-cta" data-reveal-group>
                    <div class="yq-ornament" aria-hidden="true" data-reveal><span></span></div>
                    <h2 class="yq-cta__title" id="about-contact" data-reveal>{{ __('page::about.cta_title') }}</h2>
                    <p class="yq-cta__text" data-reveal>{{ __('page::about.cta_text') }}</p>
                    <div class="yq-cta__actions" data-reveal>
                        @if ($whatsappUrl)
                            <a href="{{ $whatsappUrl }}" class="btn btn-gold" target="_blank" rel="noopener">
                                <x-icon name="message-circle" size="18" />
                                {{ __('front.contact.whatsapp_cta') }}
                                <span class="visually-hidden">{{ __('front.contact.opens_new_tab') }}</span>
                            </a>
                        @endif
                        @if ($phone)
                            <a href="{{ $phone['url'] }}" class="btn btn-outline-gold"
                                aria-label="{{ __('front.contact.phone', ['number' => $phone['label']]) }}">
                                <x-icon name="phone" size="18" />
                                <span dir="ltr">{{ $phone['label'] }}</span>
                            </a>
                        @endif
                        <a href="{{ $contactUrl }}" class="yq-underline">{{ __('front.nav.contact') }}</a>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
