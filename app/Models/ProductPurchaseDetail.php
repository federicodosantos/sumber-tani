<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPurchaseDetail extends Model
{
    protected $table = 'product_purchase_details';

    protected $fillable = [
        'product_id',
        'product_code',
        'product_name',
        'unit',
        'het_price',
        'basic_discount',
        'additional_discount',
        'net_price',
        'price',
        'quantity',
        'subtotal',
    ];

    protected $casts = [
        'het_price' => 'decimal:2',
        'basic_discount' => 'decimal:2',
        'additional_discount' => 'decimal:2',
        'net_price' => 'decimal:2',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(ProductPurchase::class, 'product_purchase_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
