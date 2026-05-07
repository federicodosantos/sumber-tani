<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use LogsActivity;

    const TYPE_PURCHASE = 'purchasement';
    const TYPE_DEBT_PAYMENT = 'debt_payment';

    protected $table = 'invoices';

    protected $fillable = ['customer_id', 'transaction_id', 'debts', 'type', 'inv_code', 'note', 'created_at', 'updated_at'];

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

    /**
     * Get the debt payment record that uses this invoice as a receipt.
     */
    public function debtPayment()
    {
        return $this->hasOne(DebtPayment::class, 'payment_invoice_id', 'id');
    }

    /**
     * Generate a unique inv_code with daily-reset numbering.
     *
     * @param string $type 'purchasement' or 'debt_payment'
     * @return string e.g. PRC180426-001 or PAY180426-001
     */
    public static function generateInvCode(string $type): string
    {
        $prefix = $type === self::TYPE_DEBT_PAYMENT ? 'PAY' : 'PRC';
        $dateStr = Carbon::now()->format('dmy');
        $pattern = $prefix . $dateStr . '-';

        // Find the latest invoice with this prefix for today
        $latest = DB::table('invoices')
            ->where('inv_code', 'like', $pattern . '%')
            ->orderBy('inv_code', 'desc')
            ->value('inv_code');

        if ($latest) {
            // Extract the number part after the last dash
            $lastNumber = (int) substr($latest, strrpos($latest, '-') + 1);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . $dateStr . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
