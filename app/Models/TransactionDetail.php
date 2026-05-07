<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDetail extends Model
{
    protected $table = 'transaction_details';
    protected $fillable = ['transaction_id', 'product_id', 'product_stock_id', 'product_price', 'buying_price', 'quantity', 'total_price', 'created_at', 'updated_at'];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productStock(): BelongsTo
    {
        return $this->belongsTo(ProductStock::class, 'product_stock_id');
    }
}
