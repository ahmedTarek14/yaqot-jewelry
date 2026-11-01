<?php

namespace App\Support;

use Modules\Setting\Services\SettingService;

/**
 * The store's contact details from Settings, as ready-to-render links. Every
 * value is null (or the list empty) when its setting is missing or invalid,
 * so views simply hide what is not configured. Used by the footer, the home
 * page's contact section and the contact page.
 */
final class SiteContact
{
    public const SOCIAL_NETWORKS = ['facebook', 'instagram', 'tiktok'];

    public function __construct(
        private readonly SettingService $settings,
        private readonly WhatsApp $whatsApp,
    ) {}

    public function whatsappUrl(): ?string
    {
        return $this->whatsApp->chatUrl();
    }

    /**
     * @return array{label: string, url: string}|null
     */
    public function phone(): ?array
    {
        $phone = $this->settings->get('phone');
        $url = EgyptianPhone::telUri($phone);

        return $url === null ? null : ['label' => EgyptianPhone::normalizePhone($phone), 'url' => $url];
    }

    /**
     * @return array{label: string, url: string}|null
     */
    public function email(): ?array
    {
        $email = $this->settings->get('email');

        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return ['label' => $email, 'url' => 'mailto:'.$email];
    }

    /**
     * Social profiles with an https URL, in a fixed order.
     *
     * @return array<int, array{network: string, label: string, url: string}>
     */
    public function socials(): array
    {
        $links = [];

        foreach (self::SOCIAL_NETWORKS as $network) {
            $url = $this->settings->get("{$network}_url");

            if (is_string($url) && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https') {
                $links[] = ['network' => $network, 'label' => __("front.social.$network"), 'url' => $url];
            }
        }

        return $links;
    }
}
