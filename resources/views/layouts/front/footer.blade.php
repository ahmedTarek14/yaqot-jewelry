{{-- $footer comes from App\View\Composers\FooterComposer; each column hides when it has nothing to show. --}}
<footer class="yq-footer">
    <div class="container">
        <div class="row gy-5" data-reveal>
            <div class="col-lg-4">
                <x-logo size="footer" />
                @foreach ($footer['text'] as $paragraph)
                    <p class="yq-footer__text yq-text">{{ $paragraph }}</p>
                @endforeach
            </div>

            <div class="col-sm-6 col-lg">
                <nav aria-labelledby="footer-explore">
                    <h2 class="yq-footer__heading" id="footer-explore">{{ __('front.footer.explore') }}</h2>
                    <ul class="yq-footer__list">
                        @foreach ($frontNav as $item)
                            <li><a href="{{ $item['url'] }}" class="yq-underline">{{ $item['label'] }}</a></li>
                        @endforeach
                        @if ($privacyUrl)
                            <li><a href="{{ $privacyUrl }}" class="yq-underline">{{ __('front.footer.privacy') }}</a></li>
                        @endif
                    </ul>
                </nav>
            </div>

            @if ($footer['phone'] || $footer['email'] || $footer['whatsappUrl'] || $footer['socials'])
                <div class="col-sm-6 col-lg">
                    <h2 class="yq-footer__heading">{{ __('front.footer.contact') }}</h2>
                    <ul class="yq-footer__list">
                        @if ($footer['phone'])
                            <li>
                                <a href="{{ $footer['phone']['url'] }}" class="yq-footer__contact"
                                    aria-label="{{ __('front.contact.phone', ['number' => $footer['phone']['label']]) }}">
                                    <x-icon name="phone" size="16" /><span class="yq-underline" dir="ltr">{{ $footer['phone']['label'] }}</span>
                                </a>
                            </li>
                        @endif
                        @if ($footer['whatsappUrl'])
                            <li>
                                <a href="{{ $footer['whatsappUrl'] }}" class="yq-footer__contact" target="_blank" rel="noopener">
                                    <x-icon name="message-circle" size="16" /><span class="yq-underline">{{ __('front.contact.whatsapp') }}</span>
                                    <span class="visually-hidden">{{ __('front.contact.opens_new_tab') }}</span>
                                </a>
                            </li>
                        @endif
                        @if ($footer['email'])
                            <li>
                                <a href="{{ $footer['email']['url'] }}" class="yq-footer__contact">
                                    <x-icon name="mail" size="16" /><span class="yq-underline" dir="ltr">{{ $footer['email']['label'] }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                    @if ($footer['socials'])
                        <ul class="yq-footer__social" aria-label="{{ __('front.footer.follow') }}">
                            @foreach ($footer['socials'] as $social)
                                <li>
                                    <a href="{{ $social['url'] }}" class="yq-underline" target="_blank" rel="noopener">{{ $social['label'] }}<span
                                            class="visually-hidden"> {{ __('front.contact.opens_new_tab') }}</span></a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @if ($footer['branches']->isNotEmpty())
                <div class="col-lg-3">
                    <h2 class="yq-footer__heading">{{ __('front.footer.branches') }}</h2>
                    <ul class="yq-footer__list yq-footer__branches">
                        @foreach ($footer['branches'] as $branch)
                            <li>
                                <span class="yq-footer__branch">{{ $branch->name }}</span>
                                @if ($branch->phone_url)
                                    <a href="{{ $branch->phone_url }}" class="yq-underline" dir="ltr"
                                        aria-label="{{ __('front.contact.phone', ['number' => $branch->phone]) }}">{{ $branch->phone }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if ($contactUrl)
                        <a href="{{ $contactUrl }}" class="yq-underline">{{ __('front.footer.all_branches') }}</a>
                    @endif
                </div>
            @endif
        </div>

        <div class="yq-footer__bottom" data-reveal>
            <p class="mb-0">
                {{ __('front.footer.copyright', ['year' => now()->year, 'brand' => __('front.brand.name')]) }}
            </p>
        </div>
    </div>
</footer>
