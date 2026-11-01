{{--
    A category, collection or brand page (Product\Support\TaxonomyPage).
    Expects: $name, $description (paragraphs), $image (url or null), plus what partials.results needs.
--}}
@extends('layouts.front.master')

@section('title', $name)

@section('content')
    <div class="yq-page-header yq-taxonomy-header">
        <div class="container">
            <x-front.breadcrumbs :items="[['label' => __('product::catalog.title'), 'url' => route('front.catalog')], ['label' => $name]]" />
            <div @class(['yq-taxonomy-header__grid', 'has-image' => $image])>
                <div class="yq-taxonomy-header__body">
                    <h1 class="yq-page-header__title">{{ $name }}</h1>
                    @foreach ($description as $paragraph)
                        <p class="yq-page-header__lead yq-text">{{ $paragraph }}</p>
                    @endforeach
                </div>
                @if ($image)
                    <span class="yq-frame yq-frame--landscape yq-taxonomy-header__media">
                        <img src="{{ $image }}" alt="" width="800" height="600">
                    </span>
                @endif
            </div>
        </div>
    </div>

    @include('product::front.partials.results')
@endsection
