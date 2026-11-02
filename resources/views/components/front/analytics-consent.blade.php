{{--
    Cookie notice for Google Analytics. $measurementId comes from a view composer registered by
    Modules\Analytics (null unless a valid id is set in Settings), so with no id nothing at all is
    rendered: no markup, no script. The notice is hidden until public/assets/js/analytics.js reveals
    it, so without JavaScript there is no notice and nothing is tracked. Google is loaded only after
    "Accept".
--}}
@if ($measurementId)
    <div class="yq-consent" role="region" aria-label="{{ __('analytics::analytics.consent.label') }}" data-analytics-consent
        data-measurement-id="{{ $measurementId }}" hidden>
        <div class="container">
            <div class="yq-consent__inner">
                <div class="yq-consent__body">
                    <p class="yq-consent__title">{{ __('analytics::analytics.consent.title') }}</p>
                    <p class="yq-consent__text">
                        {{ __('analytics::analytics.consent.text') }}
                        <a href="{{ route('front.privacy') }}" class="yq-underline">{{ __('analytics::analytics.consent.more') }}</a>
                    </p>
                </div>
                <div class="yq-consent__actions">
                    <button type="button" class="btn btn-outline-gold" data-consent-choice="denied">{{ __('analytics::analytics.consent.decline') }}</button>
                    <button type="button" class="btn btn-outline-gold" data-consent-choice="granted">{{ __('analytics::analytics.consent.accept') }}</button>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ versioned_asset('assets/js/analytics.js') }}" defer></script>
@endif
