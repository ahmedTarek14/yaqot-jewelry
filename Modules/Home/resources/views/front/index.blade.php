@extends('layouts.front.master')

@section('content')
    {{-- data-enter-group / data-enter: load sequence (front.css "Motion"). --}}
    <section class="yq-hero" aria-labelledby="yq-hero-title">
        <div class="container" data-enter-group>
            <p class="yq-eyebrow" data-enter>{{ __('home::home.hero.eyebrow') }}</p>
            <div class="yq-ornament" aria-hidden="true" data-enter><span></span></div>
            <h1 class="yq-hero__title" id="yq-hero-title" data-enter>{{ __('home::home.hero.title') }}</h1>
            <p class="yq-hero__subtitle" data-enter>{{ __('home::home.hero.subtitle') }}</p>
            @if ($catalogUrl)
                <div data-enter>
                    <a href="{{ $catalogUrl }}" class="btn btn-gold">{{ __('home::home.hero.cta') }}</a>
                </div>
            @endif
        </div>
    </section>
@endsection
