<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $textDirection }}">

<head>
    @include('layouts.admin.head')
</head>

<body>
    <main class="yq-auth" id="main">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
                    @if ($languageSwitch)
                        <p class="d-flex justify-content-end gap-3 mb-3">
                            @foreach ($languageSwitch as $language)
                                <a href="{{ $language['url'] }}" hreflang="{{ $language['code'] }}"
                                    class="yq-auth__lang text-sm d-inline-flex align-items-center gap-1"
                                    aria-label="{{ __('admin.language.switch_to', ['language' => $language['native']]) }}">
                                    <x-icon name="globe" size="16" />
                                    <span lang="{{ $language['code'] }}">{{ $language['native'] }}</span>
                                </a>
                            @endforeach
                        </p>
                    @endif

                    <div class="card yq-auth__card shadow-lg">
                        <div class="card-body p-4 p-sm-5">
                            <div class="text-center mb-4">
                                <x-logo size="login" />
                                <h1 class="h4 mt-4 mb-1">{{ __('auth::auth.login_title') }}</h1>
                                <p class="text-sm text-muted mb-0">{{ __('auth::auth.login_subtitle') }}</p>
                            </div>

                            <form method="POST" action="{{ route('admin.login.submit') }}" novalidate>
                                @csrf

                                <div class="mb-3">
                                    <label for="email" class="form-label">{{ __('auth::auth.email') }}</label>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                                        class="form-control @error('email') is-invalid @enderror" dir="ltr"
                                        autocomplete="username" required autofocus
                                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                                    @error('email')
                                        <div class="invalid-feedback" id="email-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">{{ __('auth::auth.password') }}</label>
                                    <input type="password" id="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror" dir="ltr"
                                        autocomplete="current-password" required
                                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                                    @error('password')
                                        <div class="invalid-feedback" id="password-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                        value="1" @checked(old('remember'))>
                                    <label class="form-check-label" for="remember">{{ __('auth::auth.remember') }}</label>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 mb-0">{{ __('auth::auth.submit') }}</button>
                            </form>
                        </div>
                    </div>

                    <p class="text-center mt-4 mb-0">
                        <a href="{{ $adminSiteUrl }}" class="yq-auth__back text-sm">{{ __('auth::auth.back_to_site') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <script src="{{ versioned_asset('assets/vendor/bootstrap-5.3.8/js/bootstrap.bundle.min.js') }}" defer></script>
</body>

</html>
