<footer class="yq-footer">
    <div class="container">
        <div class="row gy-4 align-items-center">
            <div class="col-lg-4">
                <x-logo />
            </div>
            <div class="col-lg-8">
                <nav aria-label="{{ __('front.nav.footer') }}">
                    <ul class="yq-footer__links justify-content-lg-end">
                        @foreach ($frontNav as $item)
                            <li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
                        @endforeach
                        @if ($privacyUrl)
                            <li><a href="{{ $privacyUrl }}">{{ __('front.footer.privacy') }}</a></li>
                        @endif
                    </ul>
                </nav>
            </div>
        </div>

        <div class="yq-footer__bottom">
            <p class="mb-0">
                {{ __('front.footer.copyright', ['year' => now()->year, 'brand' => __('front.brand.name')]) }}
            </p>
        </div>
    </div>
</footer>
