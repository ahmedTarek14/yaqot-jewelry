<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $textDirection }}">

<head>
    @include('layouts.admin.head')
</head>

<body class="g-sidenav-show bg-gray-100">
    <a href="#main" class="visually-hidden-focusable yq-skip-link">{{ __('front.skip_to_content') }}</a>

    @include('layouts.admin.sidebar')

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg" id="main" tabindex="-1">
        @include('layouts.admin.navbar')

        <div class="container-fluid py-4">
            @yield('content')
        </div>

        @include('layouts.admin.footer')
    </main>

    @include('layouts.admin.modals')

    @include('layouts.admin.scripts')
</body>

</html>
