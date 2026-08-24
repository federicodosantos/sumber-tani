<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Customer extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'customers';

    protected $fillable = ['name', 'type', 'phone_number', 'address', 'credit_balance'];

    protected $casts = [
        'credit_balance' => 'decimal:3',
    ];

    public function scopeOfType($query, ?string $type)
    {
        if ($type && in_array($type, ['r1', 'r2'], true)) {
            return $query->where('type', $type);
        }
        return $query;
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id', 'id');
    }

    public function debtPayments()
    {
        return $this->hasMany(DebtPayment::class, 'customer_id', 'id');
    }
    public function customProductPrices()
    {
        return $this->hasMany(CustomerProductPrice::class, 'customer_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('customer')
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
}
