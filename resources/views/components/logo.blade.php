{{-- Brand logo. Files and dimensions: App\View\Components\Logo + `php artisan yaqot:brand-assets`. --}}
<img src="{{ $src }}" @if ($srcset) srcset="{{ $srcset }}" @endif width="{{ $width }}" height="{{ $height }}"
    alt="{{ __('front.brand.name') }}" decoding="async" @if ($lazy) loading="lazy" @endif
    {{ $attributes->class(['yq-logo', 'yq-logo--'.$size]) }}>
