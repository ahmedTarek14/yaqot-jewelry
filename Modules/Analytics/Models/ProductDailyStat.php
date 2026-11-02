<?php

namespace Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Analytics\Database\Factories\ProductDailyStatFactory;
use Modules\Product\Models\Product;

/**
 * One row per product per day: page views and WhatsApp clicks. Aggregate counters only;
 * no IP address, user agent, cookie or any other visitor data is ever stored.
 */
class ProductDailyStat extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'date',
        'views',
        'whatsapp_clicks',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'views' => 'integer',
            'whatsapp_clicks' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function newFactory(): ProductDailyStatFactory
    {
        return ProductDailyStatFactory::new();
    }
}
