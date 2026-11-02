{{--
    Icon links to the shop's social pages (footer and contact page).

    <x-front.social-links :socials="$socials" :label="__('front.footer.follow')" class="yq-footer__social" />

    $socials comes from SiteContact::socials() (https only, only the networks that are filled in).
    Every link keeps a text name in the current language plus the "opens in a new tab" notice,
    visually hidden; the icon itself is decorative.
--}}
@props(['socials', 'label'])

@if ($socials)
    <ul {{ $attributes->class(['yq-social']) }} aria-label="{{ $label }}">
        @foreach ($socials as $social)
            <li>
                <a href="{{ $social['url'] }}" class="yq-social__link" target="_blank" rel="noopener">
                    <x-icon :name="$social['network']" size="22" />
                    <span class="visually-hidden">{{ $social['label'] }} {{ __('front.contact.opens_new_tab') }}</span>
                </a>
            </li>
        @endforeach
    </ul>
@endif
