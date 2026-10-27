<header class="yq-header">
    <div class="container yq-header__inner">
        <a href="{{ route('front.home') }}" class="yq-header__brand" aria-label="{{ __('front.brand.name') }}">
            <x-logo />
        </a>

        <nav class="d-none d-lg-block" aria-label="{{ __('front.nav.main') }}">
            <ul class="yq-nav">
                @foreach ($frontNav as $item)
                    <li>
                        <a href="{{ $item['url'] }}" class="yq-nav__link"
                            @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="yq-header__actions">
            @if ($searchUrl)
                <a href="{{ $searchUrl }}" class="yq-icon-btn" aria-label="{{ __('front.search.label') }}">
                    <x-icon name="search" />
                </a>
            @endif

            @foreach ($languageSwitch as $language)
                <a href="{{ $language['url'] }}" class="yq-lang-switch" hreflang="{{ $language['code'] }}"
                    lang="{{ $language['code'] }}"
                    aria-label="{{ __('front.language.switch_to', ['language' => $language['native']]) }}">
                    <x-icon name="globe" size="18" />
                    <span>{{ $language['native'] }}</span>
                </a>
            @endforeach

            <button type="button" class="yq-icon-btn d-lg-none" data-bs-toggle="offcanvas"
                data-bs-target="#yq-mobile-menu" aria-controls="yq-mobile-menu"
                aria-label="{{ __('front.nav.open_menu') }}">
                <x-icon name="menu" />
            </button>
        </div>
    </div>
</header>

<div class="offcanvas offcanvas-end yq-offcanvas" tabindex="-1" id="yq-mobile-menu"
    aria-labelledby="yq-mobile-menu-title">
    <div class="offcanvas-header">
        <h2 class="visually-hidden" id="yq-mobile-menu-title">{{ __('front.nav.main') }}</h2>
        <x-logo />
        <button type="button" class="yq-icon-btn" data-bs-dismiss="offcanvas"
            aria-label="{{ __('front.nav.close_menu') }}">
            <x-icon name="x" />
        </button>
    </div>
    <div class="offcanvas-body">
        <nav aria-labelledby="yq-mobile-menu-title">
            <ul class="yq-mobile-nav">
                @foreach ($frontNav as $item)
                    <li>
                        <a href="{{ $item['url'] }}"
                            @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>
</div>
