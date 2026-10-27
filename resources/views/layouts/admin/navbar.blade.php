<nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur"
    navbar-scroll="true" aria-label="{{ __('admin.title') }}">
    <div class="container-fluid py-1 px-3">
        <div>
            <nav aria-label="breadcrumb">
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
                <a href="{{ route('front.home') }}" class="btn btn-sm btn-outline-dark mb-0 d-inline-flex align-items-center gap-1"
                    target="_blank" rel="noopener">
                    <x-icon name="external-link" size="16" />
                    <span class="d-none d-sm-inline">{{ __('admin.navbar.view_site') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <form method="POST" action="{{ route('admin.logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-dark mb-0 d-inline-flex align-items-center gap-1">
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
