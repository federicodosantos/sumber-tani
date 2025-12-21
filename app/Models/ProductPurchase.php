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
