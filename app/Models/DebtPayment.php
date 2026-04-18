<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtPayment extends Model
{
    protected $table = 'debt_payments';

    protected $fillable = ['customer_id', 'payment_invoice_id', 'amount', 'payment_method', 'payment_date'];

    protected $casts = [
        'payment_date' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * The receipt invoice created for this payment.
     */
    public function paymentInvoice()
    {
        return $this->belongsTo(Invoice::class, 'payment_invoice_id', 'id');
    }

    /**
     * The detailed breakdown of which source invoices were paid.
     */
    public function details()
    {
        return $this->hasMany(DebtPaymentDetail::class, 'debt_payment_id', 'id');
    }
}
