{{--
    A responsive grid of product cards that reveal with a stagger (Motion: "Staggered group").
    <x-front.product-grid :products="$products" />   products loaded with Product::forCard()
--}}
@props(['products'])

<ul {{ $attributes->class('yq-product-grid') }} data-reveal-group>
    @foreach ($products as $product)
        <li data-reveal>
            <x-front.product-card :product="$product" />
        </li>
    @endforeach
</ul>
