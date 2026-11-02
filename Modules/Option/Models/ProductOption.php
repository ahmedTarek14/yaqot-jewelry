<?php

namespace Modules\Option\Models;

use App\Models\Concerns\HasActiveOrdering;
use App\Models\Concerns\HasProductsGuard;
use App\Models\Concerns\HasStableSlug;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use Modules\Option\Database\Factories\ProductOptionFactory;
use Modules\Product\Models\Product;

/**
 * A gold colour or a stone type the owner manages in the admin. `group` says which; the
 * products table points at it with gold_color_id / stone_type_id. Like a brand, an option
 * that any product (trashed ones included) still uses cannot be deleted, only deactivated.
 */
class ProductOption extends Model implements TranslatableContract
{
    use HasActiveOrdering;
    use HasFactory;
    use HasProductsGuard;
    use HasStableSlug;
    use Translatable;

    public const GOLD_COLOR = 'gold_color';

    public const STONE_TYPE = 'stone_type';

    /** group => the products column that points at it. */
    public const COLUMNS = [
        self::GOLD_COLOR => 'gold_color_id',
        self::STONE_TYPE => 'stone_type_id',
    ];

    public array $translatedAttributes = ['name'];

    protected $fillable = [
        'group',
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

    protected static function booted(): void
    {
        static::saving(function (ProductOption $option) {
            if (! array_key_exists($option->group, self::COLUMNS)) {
                throw new InvalidArgumentException('Invalid option group.');
            }
        });
    }

    public function scopeInGroup(Builder $query, string $group): void
    {
        $query->where($this->qualifyColumn('group'), $group);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, self::COLUMNS[$this->group] ?? self::COLUMNS[self::GOLD_COLOR]);
    }

    protected function slugSources(): array
    {
        return [$this->translate('en')?->name, $this->translate('ar')?->name];
    }

    protected static function newFactory(): ProductOptionFactory
    {
        return ProductOptionFactory::new();
    }
}
