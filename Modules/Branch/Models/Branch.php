<?php

namespace Modules\Branch\Models;

use App\Models\Concerns\HasActiveOrdering;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Branch\Database\Factories\BranchFactory;

class Branch extends Model implements TranslatableContract
{
    use HasActiveOrdering;
    use HasFactory;
    use Translatable;

    public array $translatedAttributes = ['name', 'address', 'working_hours'];

    protected $fillable = [
        'phone',
        'whatsapp',
        'map_url',
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
        // Backstop for form validation: map_url is rendered as a link on the public site.
        static::saving(function (Branch $branch) {
            $url = $branch->map_url;

            if ($url === null || $url === '') {
                return;
            }

            $scheme = parse_url($url, PHP_URL_SCHEME);

            if (! is_string($scheme) || strtolower($scheme) !== 'https') {
                throw new InvalidArgumentException('Branch map_url must be an https URL.');
            }
        });
    }

    protected static function newFactory(): BranchFactory
    {
        return BranchFactory::new();
    }
}
