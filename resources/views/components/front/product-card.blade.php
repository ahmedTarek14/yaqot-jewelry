{{--
    <x-front.product-card :product="$product" />  (App\View\Components\Front\ProductCard)
    The image alt is empty: the product name is the link's text right below it,
    so screen readers do not hear the name twice. The title is an <h3>: use the
    grid under an <h2> (a section heading, or the catalog's results heading).
--}}
<article {{ $attributes->class('yq-card') }}>
    @if ($url)
        <a href="{{ $url }}" class="yq-card__link yq-frame-link">
    @else
        <div class="yq-card__link">
    @endif
        <span class="yq-frame">
            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="" width="800" height="1000" decoding="async"
                    @if ($priority) fetchpriority="high" @elseif (! $eager) loading="lazy" @endif>
            @else
                <span class="yq-frame__placeholder"><x-icon name="gem" size="32" /></span>
            @endif
        </span>
        <div class="yq-card__body">
            @if ($brand)
                <p class="yq-card__brand">{{ $brand }}</p>
            @endif
            <h3 class="yq-card__title">{{ $product->name }}</h3>
            @if ($details)
                <p class="yq-card__details">
                    @foreach ($details as $detail)
                        <span>{{ $detail }}</span>
                    @endforeach
                </p>
            @endif
        </div>
    @if ($url)
        </a>
    @else
        </div>
    @endif
</article>
