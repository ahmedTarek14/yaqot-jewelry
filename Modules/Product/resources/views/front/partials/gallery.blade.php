{{--
    Product gallery: the main image in a .yq-frame (no reveal) and, with several images,
    thumbnail buttons that swap it (front.js "Product gallery"). Without JavaScript the
    first image is shown and <noscript> links open each full image.
--}}
<div class="yq-gallery" data-gallery data-caption="{{ __('product::show.gallery.caption') }}">
    <div class="yq-frame yq-gallery__main">
        <img src="{{ $product->images->first()->url }}" alt="{{ $product->name }}" width="800" height="1000"
            fetchpriority="high" decoding="async" data-gallery-main>
    </div>

    @if ($product->images->count() > 1)
        <p class="yq-gallery__caption" aria-live="polite" data-gallery-caption>
            {{ __('product::show.gallery.caption', ['current' => 1, 'total' => $product->images->count()]) }}
        </p>

        <ul class="yq-gallery__thumbs" aria-label="{{ __('product::show.gallery.label') }}" data-gallery-thumbs>
            @foreach ($product->images as $image)
                <li>
                    <button type="button" class="yq-gallery__thumb" data-gallery-thumb data-image-url="{{ $image->url }}"
                        aria-label="{{ __('product::show.gallery.thumbnail', ['number' => $loop->iteration]) }}"
                        aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                        @if ($loop->first) aria-current="true" @else tabindex="-1" @endif>
                        <img src="{{ $image->url }}" alt="" width="96" height="120" loading="lazy" decoding="async">
                    </button>
                </li>
            @endforeach
        </ul>

        <noscript>
            <ul class="yq-gallery__links">
                @foreach ($product->images as $image)
                    <li><a href="{{ $image->url }}" class="yq-underline">{{ __('product::show.gallery.open_image', ['number' => $loop->iteration]) }}</a></li>
                @endforeach
            </ul>
        </noscript>
    @endif
</div>
