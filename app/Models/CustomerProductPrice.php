<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProductPrice extends Model
{
    protected $table = 'customer_product_prices';

    protected $fillable = ['customer_id', 'product_id', 'custom_price'];

    protected $casts = [
        'custom_price' => 'decimal:3',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
