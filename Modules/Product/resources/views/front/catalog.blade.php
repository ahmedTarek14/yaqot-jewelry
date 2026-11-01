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

    <section class="yq-catalog" aria-labelledby="catalog-results">
        <div class="container">
            <div class="row gx-lg-5">
                <div class="col-lg-3">
                    <x-front.filter-offcanvas id="catalog-filters-panel" :title="__('product::catalog.filters.title')"
                        :close-label="__('product::catalog.filters.close')">
                        @include('product::front.partials.filters')
                    </x-front.filter-offcanvas>
                </div>

                <div class="col-lg-9">
                    <div class="yq-toolbar">
                        <h2 class="yq-toolbar__count" id="catalog-results">
                            {{ trans_choice('product::catalog.results', $products->total()) }}
                        </h2>
                        <div class="yq-toolbar__actions">
                            <button type="button" class="btn btn-outline-gold yq-toolbar__filters d-lg-none"
                                data-bs-toggle="offcanvas" data-bs-target="#catalog-filters-panel"
                                aria-controls="catalog-filters-panel">
                                <x-icon name="settings" size="16" />
                                {{ $filters->isFiltered()
                                    ? __('product::catalog.filters.open_count', ['count' => $filters->activeCount()])
                                    : __('product::catalog.filters.open') }}
                            </button>
                            <div class="yq-sort">
                                <label for="catalog-sort" class="yq-sort__label">{{ __('product::catalog.sort.label') }}</label>
                                {{-- Part of the filter form (form=""), so a sort keeps every filter. --}}
                                <select class="form-select" id="catalog-sort" name="sort" form="catalog-filters" data-autosubmit>
                                    @foreach ($filters::SORTS as $sort)
                                        <option value="{{ $sort === $filters::DEFAULT_SORT ? '' : $sort }}" @selected($filters->sort === $sort)>
                                            {{ __("product::catalog.sort.$sort") }}
                                        </option>
                                    @endforeach
                                </select>
                                <noscript>
                                    <button type="submit" form="catalog-filters" class="btn btn-outline-gold">{{ __('product::catalog.sort.apply') }}</button>
                                </noscript>
                            </div>
                        </div>
                    </div>

                    @if ($products->isEmpty())
                        @if ($filters->isFiltered())
                            <x-front.empty-state icon="search" :title="__('product::catalog.empty.filtered_title')"
                                :text="__('product::catalog.empty.filtered_text')">
                                <a href="{{ $filters->clearUrl($action) }}" class="btn btn-outline-gold">{{ __('product::catalog.filters.clear') }}</a>
                            </x-front.empty-state>
                        @else
                            <x-front.empty-state :title="__('product::catalog.empty.title')" :text="__('product::catalog.empty.text')" />
                        @endif
                    @else
                        <x-front.product-grid :products="$products" />
                        <x-front.pagination :paginator="$products" />
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
