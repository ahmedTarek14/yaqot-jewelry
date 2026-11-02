{{--
    <x-error-page code="404" />  (App\View\Components\ErrorPage)
    A complete, self-contained page in the site's style: logo, the status code, and the message in
    both languages. Static assets only; see the class for why it avoids the site layout.
--}}
<!DOCTYPE html>
<html lang="{{ $blocks[0]['locale'] }}" dir="{{ $blocks[0]['dir'] }}" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} | {{ $blocks[0]['title'] }}</title>
    <link rel="icon" href="{{ versioned_asset('favicon.ico') }}" sizes="any">
    <link rel="stylesheet" href="{{ versioned_asset('assets/vendor/bootstrap-5.3.8/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('assets/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('assets/css/front.css') }}">
</head>

<body class="yq-error">
    <main class="yq-error__main">
        <a href="{{ $homeUrl }}" class="yq-error__logo"><x-logo size="header" /></a>
        <p class="yq-error__code" aria-hidden="true">{{ $code }}</p>
        <div class="yq-ornament" aria-hidden="true"><span></span></div>

        <div class="yq-error__messages">
            @foreach ($blocks as $block)
                <section class="yq-error__message" lang="{{ $block['locale'] }}" dir="{{ $block['dir'] }}">
                    <h1 class="yq-error__title">{{ $block['title'] }}</h1>
                    <p class="yq-error__text">{{ $block['message'] }}</p>
                    <a href="{{ $homeUrl }}" class="btn btn-gold">{{ $block['home'] }}</a>
                </section>
            @endforeach
        </div>
    </main>
</body>

</html>
