<?php

namespace Modules\Setting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Setting\Database\Factories\SettingFactory;

/**
 * Not Astrotomic-translatable on purpose: the per-locale column is also named
 * `value` and would shadow this table's `value`, which non-translatable keys use.
 */
class Setting extends Model
{
    use HasFactory;

    public const CACHE_KEY = 'settings.all';

    protected $fillable = [
        'key',
        'value',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetCache());
        static::deleted(fn () => self::forgetCache());
    }

    /**
     * Forget now, and again when the surrounding transaction ends either way:
     * a read inside the transaction may have cached uncommitted values.
     */
    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);

        if (DB::transactionLevel() > 0) {
            DB::afterCommit(fn () => Cache::forget(self::CACHE_KEY));
            DB::afterRollBack(fn () => Cache::forget(self::CACHE_KEY));
        }
    }

    public function translations(): HasMany
    {
        return $this->hasMany(SettingTranslation::class);
    }

    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }
}
