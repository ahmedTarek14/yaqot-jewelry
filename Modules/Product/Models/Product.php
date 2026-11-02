<?php

namespace Modules\Product\Models;

use App\Models\Concerns\HasStableSlug;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Option\Models\ProductOption;
use Modules\Product\Database\Factories\ProductFactory;

class Product extends Model implements TranslatableContract
{
    use HasFactory;
    use HasStableSlug;
    use SoftDeletes;
    use Translatable;

    /**
     * Attribute => config('catalog.*') allowlist.
     */
    public const ALLOWLISTED = [
        'karat' => 'karats',
        'type' => 'types',
    ];

    public const JEWELRY = 'jewelry';

    public array $translatedAttributes = ['name', 'description'];

    /**
     * @var array<int, string>
     */
    private array $imagePathsToDelete = [];

    protected $fillable = [
        'sku',
        'type',
        'brand_id',
        'karat',
        'gold_color_id',
        'stone_type_id',
        'weight_grams',
        'is_published',
        'published_at',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'karat' => 'integer',
            'weight_grams' => 'decimal:3',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            // Only changed values are checked, so trimming an allowlist never breaks re-saving old products.
            foreach (self::ALLOWLISTED as $attribute => $list) {
                // Check the raw value: the integer cast would let 18.9 or '21abc' pass as an allowed karat.
                $raw = $product->getAttributes()[$attribute] ?? null;

                if ($raw === null || ! $product->isDirty($attribute)) {
                    continue;
                }

                $allowed = config("catalog.$list");
                $value = is_int($allowed[0] ?? null) ? filter_var($raw, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) : $raw;

                if (! in_array($value, $allowed, true)) {
                    throw new InvalidArgumentException("Invalid $attribute.");
                }
            }

            if ($product->is_published && $product->published_at === null) {
                $product->published_at = now();
            }
        });

        // The FK cascade removes the image rows; their files go only once the product row
        // is really gone and the transaction has committed.
        static::forceDeleting(function (Product $product) {
            $product->imagePathsToDelete = $product->images()->pluck('path')->all();
        });

        static::forceDeleted(function (Product $product) {
            $paths = $product->imagePathsToDelete;

            DB::afterCommit(fn () => Storage::disk('public')->delete($paths));
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function goldColor(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'gold_color_id');
    }

    public function stoneType(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'stone_type_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->chaperone();
    }

    /**
     * The image with the lowest sort_order; ties go to the oldest row.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)
            ->ofMany(['sort_order' => 'min', 'id' => 'min'])
            ->chaperone();
    }

    /**
     * Products the public site may show: published, with at least one image
     * and a non-blank name in every locale.
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where($this->qualifyColumn('is_published'), true)->whereHas('images');

        foreach (config('translatable.locales') as $locale) {
            $query->whereHas('translations', fn (Builder $translation) => $translation
                ->where('locale', $locale)
                ->whereRaw("TRIM(name) <> ''"));
        }
    }

    /**
     * Rings, bracelets and the like: the only type with a gold colour and stones, and the only
     * one /catalog and the category, collection and brand pages list.
     */
    public function scopeJewelry(Builder $query): void
    {
        $query->where($this->qualifyColumn('type'), self::JEWELRY);
    }

    /**
     * Gold bars and coins (the /bullion page).
     */
    public function scopeBullion(Builder $query): void
    {
        $query->whereIn($this->qualifyColumn('type'), config('catalog.bullion'));
    }

    public function isJewelry(): bool
    {
        return $this->type === self::JEWELRY;
    }

    public function scopeFeatured(Builder $query): void
    {
        $query->where($this->qualifyColumn('is_featured'), true);
    }

    public function scopeLatestPublished(Builder $query): void
    {
        $query->orderByDesc($this->qualifyColumn('published_at'))->orderByDesc($this->qualifyColumn('id'));
    }

    /**
     * Everything a public product card shows, eager-loaded (<x-front.product-card>).
     */
    public function scopeForCard(Builder $query): void
    {
        $query->with(['translations', 'primaryImage', 'brand.translations']);
    }

    /**
     * The weight for visitors, without trailing zeros ("12.5 g" / "12.5 جم"), or null.
     */
    protected function weightLabel(): Attribute
    {
        return Attribute::get(function () {
            $weight = $this->weight_grams;

            if ($weight === null) {
                return null;
            }

            $weight = str_contains($weight, '.') ? rtrim(rtrim($weight, '0'), '.') : $weight;

            return __('front.units.grams', ['weight' => $weight]);
        });
    }

    protected function slugSources(): array
    {
        return [$this->translate('en')?->name, $this->sku];
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
