<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Satu kedatangan barang untuk satu baris item nota pembelian.
 */
class ProductPurchaseReceipt extends Model
{
    use LogsActivity;

    protected $table = 'product_purchase_receipts';

    protected $fillable = [
        'product_purchase_detail_id',
        'product_stock_id',
        'quantity',
        'unit_price',
        'received_date',
        'expired_date',
        'user_id',
        'note',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'received_date' => 'date',
        'expired_date' => 'date',
    ];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(ProductPurchaseDetail::class, 'product_purchase_detail_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(ProductStock::class, 'product_stock_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('goods-receipt')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->role = auth()->user()?->role;

        $activity->properties = $activity->properties->merge([
            'ip' => request()->ip(),
        ]);
    }
}
