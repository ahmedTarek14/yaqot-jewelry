<?php

namespace Modules\Setting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Setting\Database\Factories\SettingTranslationFactory;

class SettingTranslation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'locale',
        'value',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Setting::forgetCache());
        static::deleted(fn () => Setting::forgetCache());
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class);
    }

    protected static function newFactory(): SettingTranslationFactory
    {
        return SettingTranslationFactory::new();
    }
}
