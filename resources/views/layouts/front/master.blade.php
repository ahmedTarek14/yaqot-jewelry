<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $textDirection }}" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') | @endif{{ __('front.brand.name') }}</title>
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

    <script src="{{ versioned_asset('assets/vendor/bootstrap-5.3.8/js/bootstrap.bundle.min.js') }}" defer></script>
    @stack('js')
</body>

</html>
