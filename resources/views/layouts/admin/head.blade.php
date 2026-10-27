<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow">
<title>@hasSection('title')@yield('title') | @endif{{ __('admin.title') }} · {{ __('front.brand.name') }}</title>
<link rel="icon" href="{{ versioned_asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ versioned_asset('assets/images/brand/generated/favicon-32.png') }}">
<link rel="apple-touch-icon" href="{{ versioned_asset('assets/images/brand/generated/apple-touch-icon.png') }}">

{{-- Soft UI Dashboard 3 v1.1.0 (MIT, free edition). The RTL file is the same theme mirrored. --}}
@if ($textDirection === 'rtl')
    <link rel="stylesheet" href="{{ versioned_asset('assets/admin/vendor/soft-ui-dashboard-1.1.0/css/soft-ui-dashboard-rtl.min.css') }}">
@else
    <link rel="stylesheet" href="{{ versioned_asset('assets/admin/vendor/soft-ui-dashboard-1.1.0/css/soft-ui-dashboard.min.css') }}">
@endif
<link rel="stylesheet" href="{{ versioned_asset('assets/css/fonts.css') }}">
<link rel="stylesheet" href="{{ versioned_asset('assets/admin/css/admin.css') }}">
@stack('css')
