{{-- Text wordmark: "YAQOT | ياقوت". Use an image logo only once one is supplied. --}}
@props(['size' => null])

<span {{ $attributes->class(['yq-logo', 'yq-logo--lg' => $size === 'lg']) }}>
    <span class="yq-logo__latin" lang="en">{{ __('front.brand.wordmark_latin') }}</span>
    <span class="yq-logo__rule" aria-hidden="true"></span>
    <span class="yq-logo__arabic" lang="ar">{{ __('front.brand.wordmark_arabic') }}</span>
</span>
