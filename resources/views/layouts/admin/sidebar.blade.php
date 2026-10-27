<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 yq-sidenav"
    id="sidenav-main" aria-label="{{ __('admin.nav.main') }}">
    <div class="sidenav-header">
        <button type="button" class="yq-sidenav-close d-xl-none" id="iconSidenav" aria-controls="sidenav-main"
            aria-label="{{ __('admin.nav.toggle') }}">
            <x-icon name="x" size="18" />
        </button>
        <a class="navbar-brand m-0 d-flex justify-content-center" href="{{ route('admin.dashboard') }}">
            <x-logo size="sidebar" />
        </a>
    </div>

    <hr class="horizontal dark mt-0">

    <div class="collapse navbar-collapse w-auto h-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            @foreach ($adminNav as $item)
                <li class="nav-item">
                    <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['url'] }}"
                        @if ($item['active']) aria-current="page" @endif>
                        <span class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                            <x-icon :name="$item['icon']" size="16" />
                        </span>
                        <span class="nav-link-text ms-1">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</aside>
