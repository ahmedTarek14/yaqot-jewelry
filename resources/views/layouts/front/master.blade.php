<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $textDirection }}" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Progressive enhancement: "hidden until revealed" motion styles only apply under html.js.
         Failsafe: if front.js has not run after 3s (blocked, failed to load), drop the class so content shows. --}}
    <script>document.documentElement.classList.add('js');setTimeout(function(){if(!window.yqMotionReady){document.documentElement.classList.remove('js')}},3000)</script>
    <title>@hasSection('title')@yield('title') | @endif{{ __('front.brand.name') }}</title>
    <link rel="icon" href="{{ versioned_asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ versioned_asset('assets/images/brand/generated/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ versioned_asset('assets/images/brand/generated/apple-touch-icon.png') }}">
    @foreach ($hreflangLinks as $alternate)
        <link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['url'] }}">
    @endforeach
    @stack('meta')

    @if ($textDirection === 'rtl')
        <link rel="stylesheet" href="{{ versioned_asset('assets/vendor/bootstrap-5.3.8/css/bootstrap.rtl.min.css') }}">
    @else
        <link rel="stylesheet" href="{{ versioned_asset('assets/vendor/bootstrap-5.3.8/css/bootstrap.min.css') }}">
    @endif
    <link rel="stylesheet" href="{{ versioned_asset('assets/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('assets/css/front.css') }}">
    @stack('css')
</head>

<body>
    <a href="#main" class="visually-hidden-focusable yq-skip-link">{{ __('front.skip_to_content') }}</a>

    @include('layouts.front.header')

    <main id="main" tabindex="-1">
        @yield('content')
    </main>

    @include('layouts.front.footer')

    {{-- Renders nothing unless a Google Analytics ID is set in Settings. Public pages only. --}}
    <x-front.analytics-consent />

    <script src="{{ versioned_asset('assets/vendor/jquery-3.7.1/jquery.min.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/vendor/bootstrap-5.3.8/js/bootstrap.bundle.min.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/front.js') }}" defer></script>
    @stack('js')
</body>

</html>
