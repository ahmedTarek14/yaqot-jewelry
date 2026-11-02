@extends('layouts.front.master')

@section('title', __('page::privacy.title'))

@section('content')
    <div class="yq-page-header">
        <div class="container">
            <x-front.breadcrumbs :items="[['label' => __('page::privacy.title')]]" />
            <h1 class="yq-page-header__title">{{ __('page::privacy.title') }}</h1>
            <p class="yq-page-header__lead">{{ __('page::privacy.lead') }}</p>
        </div>
    </div>

    <section class="yq-section" aria-label="{{ __('page::privacy.title') }}">
        <div class="container">
            <div class="yq-prose">
                @foreach ($sections as $section)
                    <div class="yq-prose__block" data-reveal>
                        <h2 class="yq-prose__heading">{{ __("page::privacy.$section.title") }}</h2>
                        <p class="yq-prose__text">{{ __("page::privacy.$section.text") }}</p>
                    </div>
                @endforeach

                {{-- Only with Google Analytics on: analytics.js reveals the button and re-opens the cookie notice. --}}
                @if ($analytics)
                    <div class="yq-prose__block" data-reveal>
                        <h2 class="yq-prose__heading">{{ __('page::privacy.cookie_settings.title') }}</h2>
                        <p class="yq-prose__text">{{ __('page::privacy.cookie_settings.text') }}</p>
                        <button type="button" class="btn btn-outline-gold" data-consent-open hidden>{{ __('page::privacy.cookie_settings.button') }}</button>
                    </div>
                @endif

                @if ($email)
                    <div class="yq-prose__block" data-reveal>
                        <h2 class="yq-prose__heading">{{ __('page::privacy.questions_title') }}</h2>
                        <p class="yq-prose__text">
                            {{ __('page::privacy.questions_text') }}
                            <a href="{{ $email['url'] }}" class="yq-underline" dir="ltr">{{ $email['label'] }}</a>
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
