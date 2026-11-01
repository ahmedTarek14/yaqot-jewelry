<?php

namespace Modules\Collection\Models;

use App\Models\Concerns\HasActiveOrdering;
use App\Models\Concerns\HasProductsGuard;
use App\Models\Concerns\HasPublicImage;
use App\Models\Concerns\HasStableSlug;
use App\Models\Concerns\HasVisibleProducts;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Collection\Database\Factories\CollectionFactory;
use Modules\Product\Models\Product;

class Collection extends Model implements TranslatableContract
{
    use HasActiveOrdering;
    use HasFactory;
    use HasProductsGuard;
    use HasPublicImage;
    use HasStableSlug;
    use HasVisibleProducts;
    use Translatable;

    public array $translatedAttributes = ['name', 'description'];

    protected $fillable = [
        'image_path',
        'is_active',
        'show_on_homepage',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_on_homepage' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function scopeOnHomepage(Builder $query): void
    {
        $query->where($this->qualifyColumn('show_on_homepage'), true);
    }

    protected function slugSources(): array
    {
        return [$this->translate('en')?->name, $this->translate('ar')?->name];
    }

    protected static function newFactory(): CollectionFactory
    {
        return CollectionFactory::new();
    }
}
