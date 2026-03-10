<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Customer extends Model
{
    use LogsActivity;

    protected $table = 'customers';

    protected $fillable = ['name', 'phone_number', 'address'];

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id', 'id');
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
