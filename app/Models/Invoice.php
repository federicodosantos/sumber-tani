<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Invoice extends Model
{
    use LogsActivity;

    protected $table = 'invoices';

    protected $fillable = ['customer_id', 'transaction_id', 'debts'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('invoice')
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

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }
}
