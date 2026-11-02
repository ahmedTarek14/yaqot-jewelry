<nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur"
    navbar-scroll="true" aria-label="{{ __('admin.title') }}">
    <div class="container-fluid py-1 px-3 gap-2">
        <div>
            <nav aria-label="{{ __('admin.navbar.breadcrumb') }}">
                <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0">
                    <li class="breadcrumb-item text-sm">
                        <a class="opacity-7 text-dark" href="{{ route('admin.dashboard') }}">{{ __('admin.title') }}</a>
                    </li>
                    @stack('breadcrumb')
                </ol>
            </nav>
            <h1 class="h6 font-weight-bolder mb-0">@yield('title', __('admin.title'))</h1>
        </div>

        <ul class="navbar-nav flex-row align-items-center gap-2 ms-auto">
            <li class="nav-item">
                {{-- The text is hidden below 576px, so the icon-only button needs its own accessible name. --}}
                <a href="{{ $adminSiteUrl }}" class="btn btn-sm btn-outline-dark mb-0 d-inline-flex align-items-center gap-1"
                    target="_blank" rel="noopener" aria-label="{{ __('admin.navbar.view_site') }}">
                    <x-icon name="external-link" size="16" />
                    <span class="d-none d-sm-inline">{{ __('admin.navbar.view_site') }}</span>
                </a>
            </li>
            {{-- The same page in the other language. The accessible name is in the
                 page language; the visible native name carries its own lang. --}}
            @foreach ($languageSwitch as $language)
                <li class="nav-item">
                    <a href="{{ $language['url'] }}" hreflang="{{ $language['code'] }}"
                        class="btn btn-sm btn-outline-dark mb-0 d-inline-flex align-items-center gap-1 yq-lang-switch"
                        aria-label="{{ __('admin.language.switch_to', ['language' => $language['native']]) }}">
                        <x-icon name="globe" size="16" />
                        <span lang="{{ $language['code'] }}">{{ $language['native'] }}</span>
                    </a>
                </li>
            @endforeach
            @if (Route::has('admin.account.edit'))
                <li class="nav-item">
                    <a href="{{ route('admin.account.edit') }}"
                        class="btn btn-sm btn-outline-dark mb-0 d-inline-flex align-items-center gap-1"
                        aria-label="{{ __('admin.navbar.account') }}"
                        @if (request()->routeIs('admin.account.*')) aria-current="page" @endif>
                        <x-icon name="user" size="16" />
                        <span class="d-none d-sm-inline">{{ __('admin.navbar.account') }}</span>
                    </a>
                </li>
            @endif
            <li class="nav-item">
                <form method="POST" action="{{ route('admin.logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-dark mb-0 d-inline-flex align-items-center gap-1"
                        aria-label="{{ __('admin.navbar.logout') }}">
                        <x-icon name="log-out" size="16" flip-rtl />
                        <span class="d-none d-sm-inline">{{ __('admin.navbar.logout') }}</span>
                    </button>
                </form>
            </li>
            <li class="nav-item d-xl-none">
                <button type="button" class="btn btn-sm btn-link text-body p-2 mb-0" id="iconNavbarSidenav"
                    aria-controls="sidenav-main" aria-label="{{ __('admin.nav.toggle') }}">
                    <x-icon name="menu" />
                </button>
            </li>
        </ul>
    </div>
</nav>
