@extends('layouts.front.master')

@section('title', __('product::bullion.title'))

@section('content')
    <div class="yq-page-header">
        <div class="container">
            <x-front.breadcrumbs :items="[['label' => __('product::bullion.title')]]" />
            <h1 class="yq-page-header__title">{{ __('product::bullion.title') }}</h1>
            @if ($filters->search !== null)
                <p class="yq-page-header__lead">{{ __('product::catalog.search_title', ['term' => $filters->search]) }}</p>
            @else
                <p class="yq-page-header__lead">{{ __('product::bullion.lead') }}</p>
            @endif
            <p class="yq-page-header__lead" id="bullion-price-note">{{ __('product::bullion.price_note') }}</p>
        </div>
    </div>

    @include('product::front.partials.results')
@endsection
