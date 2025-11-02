<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemCategory extends Model
{
    use SoftDeletes;
    protected $table = 'item_categories';

    protected $fillable = [
        'name',
        'description',
    ];

    protected $dates = ['deleted_at'];
}
