<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class ProductStock extends Model
{
    use SoftDeletes;

    protected $table = 'product_stocks';

    protected $fillable = [
        'product_id',
        'stock_opname',
        'price',
    ];

    protected $dates = ['deleted_at'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
