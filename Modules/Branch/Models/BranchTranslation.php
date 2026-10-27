<?php

namespace Modules\Branch\Models;

use Illuminate\Database\Eloquent\Model;

class BranchTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'address',
        'working_hours',
    ];
}
