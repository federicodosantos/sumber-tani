<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtPaymentDetail extends Model
{
    protected $table = 'debt_payment_details';

    protected $fillable = ['debt_payment_id', 'invoice_id', 'amount_paid', 'debt_before', 'debt_after'];

    /**
     * The parent debt payment.
     */
    public function debtPayment()
    {
        return $this->belongsTo(DebtPayment::class, 'debt_payment_id', 'id');
    }

    /**
     * The source invoice that was being paid off.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }
}
