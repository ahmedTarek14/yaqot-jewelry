<footer class="yq-footer">
    <div class="container">
        <div class="row gy-4 align-items-center" data-reveal>
            <div class="col-lg-4">
                <x-logo size="footer" />
            </div>
            <div class="col-lg-8">
                <nav aria-label="{{ __('front.nav.footer') }}">
                    <ul class="yq-footer__links justify-content-lg-end">
                        @foreach ($frontNav as $item)
                            <li><a href="{{ $item['url'] }}" class="yq-underline">{{ $item['label'] }}</a></li>
                        @endforeach
                        @if ($privacyUrl)
                            <li><a href="{{ $privacyUrl }}" class="yq-underline">{{ __('front.footer.privacy') }}</a></li>
                        @endif
                    </ul>
                </nav>
            </div>
        </div>

        <div class="yq-footer__bottom" data-reveal>
            <p class="mb-0">
                {{ __('front.footer.copyright', ['year' => now()->year, 'brand' => __('front.brand.name')]) }}
            </p>
        </div>
    </div>
</footer>
