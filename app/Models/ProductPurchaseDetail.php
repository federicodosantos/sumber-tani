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
        'quantity'            => 'decimal:3',
        'het_price'           => 'decimal:3',
        'basic_discount'      => 'decimal:3',
        'additional_discount' => 'decimal:3',
        'net_price'           => 'decimal:3',
        'price'               => 'decimal:3',
        'subtotal'            => 'decimal:3',
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
