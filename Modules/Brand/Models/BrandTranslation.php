<?php

namespace Modules\Brand\Models;

use Illuminate\Database\Eloquent\Model;

class BrandTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];
}
