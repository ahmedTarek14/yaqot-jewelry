<?php

namespace Modules\Product\Models;

use App\Models\Concerns\HasPublicImage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Database\Factories\ProductImageFactory;

class ProductImage extends Model
{
    use HasFactory;
    use HasPublicImage;

    protected $fillable = [
        'path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk('public')->url($this->path));
    }

    /**
     * Alt text defaults to the product name in the current locale.
     * Loaded through Product::images()/primaryImage(), the parent is already set (chaperone).
     */
    protected function alt(): Attribute
    {
        return Attribute::get(fn () => $this->product?->name);
    }

    protected function publicImageColumn(): string
    {
        return 'path';
    }

    protected static function newFactory(): ProductImageFactory
    {
        return ProductImageFactory::new();
    }
}
