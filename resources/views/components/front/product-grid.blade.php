{{--
    A responsive grid of product cards that reveal with a stagger (Motion: "Staggered group").
    <x-front.product-grid :products="$products" />   products loaded with Product::forCard()
    :eager="4" loads the first four images at once (the first with high priority) instead of lazily:
    use it on a listing whose grid starts above the fold, where the first image is the page's LCP element.
--}}
@props(['products', 'eager' => 0])

<ul {{ $attributes->class('yq-product-grid') }} data-reveal-group>
    @foreach ($products as $product)
        <li data-reveal>
            <x-front.product-card :product="$product" :eager="$loop->index < $eager" :priority="$loop->first && $eager > 0" />
        </li>
    @endforeach
</ul>
