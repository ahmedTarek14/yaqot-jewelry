@extends('layouts.front.master')

@section('title', __('page::contact.title'))

@section('content')
    <div class="yq-page-header">
        <div class="container">
            <x-front.breadcrumbs :items="[['label' => __('page::contact.title')]]" />
            <h1 class="yq-page-header__title">{{ __('page::contact.title') }}</h1>
            <p class="yq-page-header__lead">{{ __('page::contact.lead') }}</p>
        </div>
    </div>

    <section class="yq-section yq-contact" aria-label="{{ __('page::contact.title') }}">
        <div class="container">
            @if ($hasDirect)
                <div class="yq-contact__direct" data-reveal>
                    <h2 class="yq-contact__heading">{{ __('page::contact.direct_title') }}</h2>
                    <ul class="yq-contact__list">
                        @if ($whatsappUrl)
                            <li>
                                <a href="{{ $whatsappUrl }}" class="btn btn-gold" target="_blank" rel="noopener">
                                    <x-icon name="message-circle" size="18" />
                                    {{ __('front.contact.whatsapp_cta') }}
                                    <span class="visually-hidden">{{ __('front.contact.opens_new_tab') }}</span>
                                </a>
                            </li>
                        @endif
                        @if ($phone)
                            <li>
                                <a href="{{ $phone['url'] }}" class="yq-contact__link"
                                    aria-label="{{ __('front.contact.phone', ['number' => $phone['label']]) }}">
                                    <x-icon name="phone" size="18" />
                                    <span class="yq-underline" dir="ltr">{{ $phone['label'] }}</span>
                                </a>
                            </li>
                        @endif
                        @if ($email)
                            <li>
                                <a href="{{ $email['url'] }}" class="yq-contact__link">
                                    <x-icon name="mail" size="18" />
                                    <span class="yq-underline" dir="ltr">{{ $email['label'] }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                    <x-front.social-links :socials="$socials" :label="__('page::contact.follow')" class="yq-contact__social" />
                </div>
            @endif

            @if ($branches->isNotEmpty())
                <h2 class="yq-contact__heading yq-contact__heading--branches" id="contact-branches">{{ __('page::contact.branches_title') }}</h2>
                <ul class="yq-branch-grid" aria-labelledby="contact-branches" data-reveal-group>
                    @foreach ($branches as $branch)
                        <li class="yq-branch" data-reveal>
                            <h3 class="yq-branch__name">{{ $branch['name'] }}</h3>
                            @if ($branch['address'])
                                <p class="yq-branch__row">
                                    <x-icon name="map-pin" size="18" />
                                    <span><span class="visually-hidden">{{ __('page::contact.address') }}: </span><span class="yq-text">{{ $branch['address'] }}</span></span>
                                </p>
                            @endif
                            @if ($branch['hours'])
                                <div class="yq-branch__hours">
                                    <p class="yq-branch__label">{{ __('page::contact.working_hours') }}</p>
                                    <p class="yq-text mb-0">{{ $branch['hours'] }}</p>
                                </div>
                            @endif
                            @if ($branch['phoneUrl'] || $branch['whatsappUrl'] || $branch['mapUrl'])
                                <ul class="yq-branch__actions">
                                    @if ($branch['phoneUrl'])
                                        <li>
                                            <a href="{{ $branch['phoneUrl'] }}" class="yq-contact__link"
                                                aria-label="{{ __('front.contact.phone', ['number' => $branch['phone']]) }}">
                                                <x-icon name="phone" size="16" />
                                                <span class="yq-underline" dir="ltr">{{ $branch['phone'] }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if ($branch['whatsappUrl'])
                                        <li>
                                            <a href="{{ $branch['whatsappUrl'] }}" class="yq-contact__link" target="_blank" rel="noopener">
                                                <x-icon name="message-circle" size="16" />
                                                <span class="yq-underline">{{ __('front.contact.whatsapp') }}</span>
                                                <span class="visually-hidden">{{ __('front.contact.opens_new_tab') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if ($branch['mapUrl'])
                                        <li>
                                            <a href="{{ $branch['mapUrl'] }}" class="yq-contact__link" target="_blank" rel="noopener">
                                                <x-icon name="external-link" size="16" />
                                                <span class="yq-underline">{{ __('page::contact.map') }}</span>
                                                <span class="visually-hidden">{{ __('front.contact.opens_new_tab') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if (! $hasDirect && $branches->isEmpty())
                <x-front.empty-state icon="phone" :title="__('page::contact.empty_title')" :text="__('page::contact.empty_text')" />
            @endif
        </div>
    </section>
@endsection
