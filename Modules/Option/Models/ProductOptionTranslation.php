<?php

namespace Modules\Option\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOptionTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];
}
