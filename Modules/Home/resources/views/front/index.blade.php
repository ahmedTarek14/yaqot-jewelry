@extends('layouts.front.master')

@section('content')
    <section class="yq-hero" aria-labelledby="yq-hero-title">
        <div class="container">
            <p class="yq-eyebrow">{{ __('home::home.hero.eyebrow') }}</p>
            <div class="yq-ornament" aria-hidden="true"><span></span></div>
            <h1 class="yq-hero__title" id="yq-hero-title">{{ __('home::home.hero.title') }}</h1>
            <p class="yq-hero__subtitle">{{ __('home::home.hero.subtitle') }}</p>
            @if ($catalogUrl)
                <a href="{{ $catalogUrl }}" class="btn btn-gold">{{ __('home::home.hero.cta') }}</a>
            @endif
        </div>
    </section>
@endsection
