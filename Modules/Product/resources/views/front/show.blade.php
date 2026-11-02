@extends('layouts.front.master')

@section('title', $title)

@section('content')
    <div class="yq-page-header yq-product__crumbs">
        <div class="container">
            <x-front.breadcrumbs :items="array_values(array_filter([
                ['label' => $listLabel, 'url' => $listUrl],
                $categoryCrumb ? ['label' => $categoryCrumb->name, 'url' => route('front.categories.show', $categoryCrumb->slug)] : null,
                ['label' => $product->name],
            ]))" />
        </div>
    </div>

    <article class="yq-product">
        <div class="container">
            <div class="row gx-lg-5 gy-4">
                <div class="col-lg-7">
                    @include('product::front.partials.gallery')
                </div>

                <div class="col-lg-5">
                    <div class="yq-product__info">
                        @if ($brand)
                            <p class="yq-product__brand">
                                <span class="visually-hidden">{{ __('product::show.brand') }}:</span>
                                <a href="{{ $brandUrl }}" class="yq-underline">{{ $brand->name }}</a>
                            </p>
                        @endif

                        <h1 class="yq-product__title">{{ $product->name }}</h1>

                        <h2 class="yq-product__subheading">{{ __('product::show.details') }}</h2>
                        <dl class="yq-specs">
                            @foreach ($specs as $spec)
                                <div class="yq-specs__row">
                                    <dt>{{ $spec['label'] }}</dt>
                                    <dd>{{ $spec['value'] }}</dd>
                                </div>
                            @endforeach
                            <div class="yq-specs__row">
                                <dt>{{ __('product::show.attributes.sku') }}</dt>
                                <dd><code class="yq-specs__code" dir="ltr">{{ $product->sku }}</code></dd>
                            </div>
                        </dl>

                        @if ($paragraphs)
                            <div class="yq-product__description">
                                <h2 class="yq-product__subheading">{{ __('product::show.description') }}</h2>
                                @foreach ($paragraphs as $paragraph)
                                    <p class="yq-text">{{ $paragraph }}</p>
                                @endforeach
                            </div>
                        @endif

                        @if ($categories->isNotEmpty())
                            <div class="yq-product__terms">
                                <h2 class="yq-product__subheading">{{ __('product::show.categories') }}</h2>
                                <ul class="yq-chips list-unstyled">
                                    @foreach ($categories as $category)
                                        <li><a href="{{ route('front.categories.show', $category->slug) }}" class="yq-chip">{{ $category->name }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($collections->isNotEmpty())
                            <div class="yq-product__terms">
                                <h2 class="yq-product__subheading">{{ __('product::show.collections') }}</h2>
                                <ul class="yq-chips list-unstyled">
                                    @foreach ($collections as $collection)
                                        <li><a href="{{ route('front.collections.show', $collection->slug) }}" class="yq-chip">{{ $collection->name }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="yq-product__cta">
                            @if ($whatsappUrl)
                                <a href="{{ $whatsappUrl }}" class="btn btn-gold btn-lg" target="_blank" rel="noopener">
                                    <x-icon name="message-circle" size="20" />
                                    {{ __('front.contact.whatsapp_cta') }}
                                    <span class="visually-hidden">{{ __('front.contact.opens_new_tab') }}</span>
                                </a>
                            @else
                                <a href="{{ $contactUrl }}" class="btn btn-outline-gold btn-lg">{{ __('product::show.contact_link') }}</a>
                            @endif
                            <p class="yq-product__note">{{ __('product::show.price_note') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="yq-section yq-section--alt yq-product__related" aria-labelledby="product-related">
            <div class="container">
                <x-front.section-heading id="product-related" :title="__('product::show.related')" />
                <x-front.product-grid :products="$related" />
            </div>
        </section>
    @endif
@endsection
