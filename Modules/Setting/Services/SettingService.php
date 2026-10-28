<?php

namespace Modules\Setting\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Setting\Models\Setting;

class SettingService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $translatable = $this->isTranslatable($key);
        $row = $this->rows()[$key] ?? null;

        if ($row === null) {
            return $default;
        }

        if (! $translatable) {
            return $this->filled($row['value']) ? $row['value'] : $default;
        }

        foreach ([app()->getLocale(), config('translatable.fallback_locale')] as $locale) {
            $value = $row['translations'][$locale] ?? null;

            if ($this->filled($value)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Every registered key resolved for the current locale (null when unset).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $values = [];

        foreach (array_keys($this->keys()) as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * The stored values as the admin form names them, without fallbacks: `{key}` for
     * plain keys and `{key}_{locale}` for every locale of a translatable key (null when unset).
     *
     * @return array<string, ?string>
     */
    public function formValues(): array
    {
        $rows = $this->rows();
        $values = [];

        foreach ($this->keys() as $key => $definition) {
            if (! ($definition['translatable'] ?? false)) {
                $values[$key] = $rows[$key]['value'] ?? null;

                continue;
            }

            foreach (config('translatable.locales') as $locale) {
                $values["{$key}_{$locale}"] = $rows[$key]['translations'][$locale] ?? null;
            }
        }

        return $values;
    }

    /**
     * Translatable keys take an array keyed by locale; only the given locales are written.
     */
    public function set(string $key, mixed $value): void
    {
        $this->setMany([$key => $value]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->validate((string) $key, $value);
        }

        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                $this->write((string) $key, $value);
            }
        });

        // The model listeners already forgot the cache, but a concurrent read may
        // have re-primed it with pre-commit data before the transaction committed.
        Setting::forgetCache();
    }

    public function flush(): void
    {
        Setting::forgetCache();
    }

    private function write(string $key, mixed $value): void
    {
        if (! $this->isTranslatable($key)) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);

            return;
        }

        $setting = Setting::query()->firstOrCreate(['key' => $key]);

        foreach ($value as $locale => $localized) {
            $setting->translations()->updateOrCreate(['locale' => $locale], ['value' => $localized]);
        }
    }

    private function validate(string $key, mixed $value): void
    {
        if (! $this->isTranslatable($key)) {
            if (! is_null($value) && ! is_scalar($value)) {
                throw new InvalidArgumentException("Setting [$key] expects a scalar value.");
            }

            return;
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException("Setting [$key] is translatable and expects an array keyed by locale.");
        }

        $locales = config('translatable.locales');

        foreach ($value as $locale => $localized) {
            if (! in_array($locale, $locales, true)) {
                throw new InvalidArgumentException("Unsupported locale [$locale] for setting [$key].");
            }

            if (! is_null($localized) && ! is_scalar($localized)) {
                throw new InvalidArgumentException("Setting [$key.$locale] expects a scalar value.");
            }
        }
    }

    private function isTranslatable(string $key): bool
    {
        $definition = $this->keys()[$key] ?? null;

        if ($definition === null) {
            throw new InvalidArgumentException("Unknown setting [$key].");
        }

        return (bool) ($definition['translatable'] ?? false);
    }

    /**
     * @return array<string, array{translatable?: bool}>
     */
    private function keys(): array
    {
        return config('setting.keys', []);
    }

    /**
     * Locale-independent snapshot: key => ['value' => ..., 'translations' => [locale => value]].
     *
     * @return array<string, array{value: ?string, translations: array<string, ?string>}>
     */
    private function rows(): array
    {
        // Saves invalidate the cache; the TTL only bounds the rare race where a
        // read that started before a commit stores its result after the flush.
        return Cache::remember(Setting::CACHE_KEY, now()->addHour(), function () {
            return Setting::query()
                ->with('translations')
                ->get()
                ->mapWithKeys(fn (Setting $setting) => [
                    $setting->key => [
                        'value' => $setting->value,
                        'translations' => $setting->translations->pluck('value', 'locale')->all(),
                    ],
                ])
                ->all();
        });
    }

    private function filled(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }
}
