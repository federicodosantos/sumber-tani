<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductPurchase extends Model
{
    use LogsActivity;
    protected $table = 'product_purchases';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('product')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        // User role is stored as a plain column, not a relation
        $activity->role = auth()->user()?->role;

        $activity->properties = $activity->properties->merge([
            'ip' => request()->ip(),
        ]);
    }

    protected $fillable = [
        'purchase_date',
        'total_items',
        'subtotal',
        'discount_type',
        'discount_percent',
        'discount_value',
        'ppn_type',
        'ppn_percent',
        'ppn_value',
        'grand_total',
        'payment_method',
        'is_paid',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_items'   => 'decimal:3',
        'subtotal'      => 'decimal:3',
        'discount_percent' => 'decimal:3',
        'discount_value'   => 'decimal:3',
        'ppn_type'      => 'string',
        'ppn_percent'   => 'decimal:3',
        'ppn_value'     => 'decimal:3',
        'grand_total'   => 'decimal:3',
        'is_paid'       => 'boolean',
    ];

    public function getManualGrandTotalAttribute()
    {
        $subtotal = $this->subtotal ?? 0;
        $discountValue = $this->discount_value ?? 0;
        $afterDiscount = $subtotal - $discountValue;
        $ppnValue = $this->ppn_value ?? 0;
        $systemGrandTotal = $afterDiscount + $ppnValue;

        // If the saved grand_total differs from the system grand total by more than 1 rupiah, it was manually set
        if (abs($this->grand_total - $systemGrandTotal) > 1) {
            return $this->grand_total;
        }

        return null;
    }

    public function details(): HasMany
    {
        return $this->hasMany(ProductPurchaseDetail::class, 'product_purchase_id');
    }
}
