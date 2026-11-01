@extends('layouts.front.master')

@section('title', __('product::catalog.title'))

@section('content')
    <div class="yq-page-header">
        <div class="container">
            <x-front.breadcrumbs :items="[['label' => __('product::catalog.title')]]" />
            <h1 class="yq-page-header__title">{{ __('product::catalog.title') }}</h1>
            @if ($filters->search !== null)
                <p class="yq-page-header__lead">{{ __('product::catalog.search_title', ['term' => $filters->search]) }}</p>
            @endif
        </div>
    </div>

    @include('product::front.partials.results')
@endsection
