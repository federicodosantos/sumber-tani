<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'total_quantity',
        'total_price',
        'created_at',
        'updated_at',
        'offline_uuid',
        'discount',
        'payment_method',
        'is_paid',
    ];

    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class, 'transaction_id');
    }
}
