<?php

namespace App\Support;

use Modules\Setting\Services\SettingService;

/**
 * WhatsApp click-to-chat links.
 *
 * Numbers are normalised with EgyptianPhone::normalizeMobile(): 01…, +201…,
 * 00201… and 201… all become 201XXXXXXXXX, the form wa.me expects. Anything
 * else (a landline, a hotline, a typo) gives no link, so the CTA is hidden.
 *
 * The configured number is the `whatsapp_number` setting; messages are
 * written in the current locale (lang/{ar,en}/front.php "whatsapp").
 */
final class WhatsApp
{
    public function __construct(private readonly SettingService $settings) {}

    public static function normalize(?string $number): ?string
    {
        return EgyptianPhone::normalizeMobile($number);
    }

    /**
     * https://wa.me/{number}?text={message}, or null when the number is not a valid
     * Egyptian mobile number. The message is encoded with rawurlencode (spaces as %20).
     */
    public static function link(?string $number, ?string $message = null): ?string
    {
        $normalized = self::normalize($number);

        if ($normalized === null) {
            return null;
        }

        $message = $message === null ? '' : trim($message);

        return 'https://wa.me/'.$normalized.($message === '' ? '' : '?text='.rawurlencode($message));
    }

    /**
     * The store's number as 201XXXXXXXXX, or null when none (or an invalid one) is configured.
     */
    public function number(): ?string
    {
        return self::normalize($this->settings->get('whatsapp_number'));
    }

    public function isConfigured(): bool
    {
        return $this->number() !== null;
    }

    /**
     * A chat with the store, opened with a general greeting.
     */
    public function chatUrl(): ?string
    {
        return self::link($this->number(), __('front.whatsapp.general_message'));
    }

    /**
     * A chat about one product: greeting, product name, SKU, page URL and an
     * availability question, in the current locale.
     */
    public function productUrl(string $name, string $sku, string $url): ?string
    {
        return self::link($this->number(), __('front.whatsapp.product_message', [
            'name' => $name,
            'sku' => $sku,
            'url' => $url,
        ]));
    }
}
