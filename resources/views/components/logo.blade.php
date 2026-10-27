{{-- Brand logo. Files and dimensions: App\View\Components\Logo + `php artisan yaqot:brand-assets`.
     glint: the signature light sweep (front.css "Motion"); the mask is the same image, passed as a CSS variable. --}}
@if ($glint)
    <span class="yq-glint" style="--yq-logo-mask: url('{{ $maskUrl }}')">
@endif
<img src="{{ $src }}" @if ($srcset) srcset="{{ $srcset }}" @endif width="{{ $width }}" height="{{ $height }}"
    alt="{{ __('front.brand.name') }}" decoding="async" @if ($lazy) loading="lazy" @endif
    {{ $attributes->class(['yq-logo', 'yq-logo--'.$size]) }}>
@if ($glint)
    <span class="yq-glint__shine" aria-hidden="true"></span>
    </span>
@endif
