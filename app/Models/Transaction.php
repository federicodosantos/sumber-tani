<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Transaction extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('product')->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->role = auth()->user()?->role;

        $activity->properties = $activity->properties->merge([
            'ip' => request()->ip(),
        ]);
    }

    protected $table = 'transactions';

    protected $fillable = ['total_quantity', 'total_price', 'created_at', 'updated_at', 'offline_uuid', 'discount', 'payment_method', 'is_paid', 'cash_received', 'change_amount', 'is_manual', 'transaction_date'];

    protected $casts = [
        'total_quantity' => 'decimal:3',
        'total_price' => 'decimal:3',
        'discount' => 'decimal:3',
        'is_paid' => 'boolean',
        'cash_received' => 'decimal:3',
        'change_amount' => 'decimal:3',
        'is_manual' => 'boolean',
        'transaction_date' => 'datetime',
    ];

    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class, 'transaction_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'transaction_id');
    }
}
