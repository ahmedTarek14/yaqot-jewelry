<?php

namespace Modules\Analytics\Support;

/**
 * The optional Google Analytics 4 measurement id (Settings, "ga_measurement_id"). With no id
 * the public site loads nothing from Google, shows no notice and sets no cookie.
 */
final class GoogleAnalytics
{
    /** A GA4 measurement id, e.g. G-ABC123XYZ9. Shared by the request rule and the output check. */
    public const PATTERN = '/^G-[A-Z0-9]{4,12}$/';

    /**
     * The configured id when it is well formed (so it is safe in an attribute), else null.
     */
    public static function measurementId(): ?string
    {
        $id = setting('ga_measurement_id');

        return is_string($id) && preg_match(self::PATTERN, $id) === 1 ? $id : null;
    }

    public static function enabled(): bool
    {
        return self::measurementId() !== null;
    }
}
