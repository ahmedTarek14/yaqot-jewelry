<?php

namespace Modules\Brand\Models;

use App\Models\Concerns\HasActiveOrdering;
use App\Models\Concerns\HasProductsGuard;
use App\Models\Concerns\HasStableSlug;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Brand\Database\Factories\BrandFactory;
use Modules\Product\Models\Product;

class Brand extends Model implements TranslatableContract
{
    use HasActiveOrdering;
    use HasFactory;
    use HasProductsGuard;
    use HasStableSlug;
    use Translatable;

    public array $translatedAttributes = ['name'];

    protected $fillable = [
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected function slugSources(): array
    {
        return [$this->translate('en')?->name, $this->translate('ar')?->name];
    }

    protected static function newFactory(): BrandFactory
    {
        return BrandFactory::new();
    }
}
