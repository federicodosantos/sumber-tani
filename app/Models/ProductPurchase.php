<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPurchase extends Model
{
    protected $table = 'product_purchases';

    protected $fillable = [
        'purchase_date',
        'total_items',
        'subtotal',
        'discount_percent',
        'discount_value',
        'ppn_percent',
        'ppn_value',
        'grand_total',
        'payment_method',
        'is_paid',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'ppn_percent' => 'decimal:2',
        'ppn_value' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'is_paid' => 'boolean',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(ProductPurchaseDetail::class, 'product_purchase_id');
    }
}
