<?php

namespace App\Services;

use App\Models\CustomerProductPrice;
use App\Models\DebtPayment;
use App\Models\DebtPaymentDetail;
use App\Models\Invoice;
use App\Models\ProductStock;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransactionReversalService
{
    /**
     * Hard delete a transaction, restoring stock and reversing all
     * related invoices and debt payments.
     */
    public function reverseAndDelete(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->loadMissing(['transactionDetails', 'invoices']);

            $snapshot = $this->snapshotForLog($transaction);

            $this->reverseStockForDetails($transaction->transactionDetails);

            foreach ($transaction->invoices as $invoice) {
                $this->reverseInvoiceFully($invoice);
            }

            $transaction->transactionDetails()->delete();
            $transaction->delete();

            activity('finance')
                ->causedBy(auth()->user())
                ->withProperties($snapshot)
                ->event('transaction_deleted')
                ->log('Transaksi #' . $snapshot['transaction_id'] . ' dihapus dan stok dikembalikan');
        });
    }

    /**
     * Update a transaction: reverse old stock, apply new items,
     * recompute invoice debts. Throws if data is inconsistent.
     *
     * $payload structure mirrors the cashier store payload:
     *   items: [{id, price, qty, basePrice?}]
     *   totalQty, totalAmount, discount, payment_method, is_paid,
     *   cash_received, change_amount, transaction_date
     */
    public function updateTransaction(Transaction $transaction, array $payload): Transaction
    {
        return DB::transaction(function () use ($transaction, $payload) {
            $transaction->loadMissing(['transactionDetails', 'invoices']);

            $before = $this->snapshotForLog($transaction);

            // 1. Reverse stock from existing details
            $this->reverseStockForDetails($transaction->transactionDetails);

            // 2. Drop existing details
            $transaction->transactionDetails()->delete();

            // 3. Resolve transaction date (the business date, not record creation time)
            $trxDate = !empty($payload['transaction_date'])
                ? Carbon::parse($payload['transaction_date'])->setTimezone(config('app.timezone'))
                : ($transaction->transaction_date ?? $transaction->created_at);

            // 4. Apply new items (decrement stock, persist new details)
            foreach ($payload['items'] as $item) {
                $stock = ProductStock::where('product_id', $item['id'])
                    ->whereNull('deleted_at')
                    ->where('stock_opname', '>=', (int) $item['qty'])
                    ->orderBy('created_at', 'asc')
                    ->first();

                if (! $stock) {
                    throw new RuntimeException(
                        'Stok tidak cukup untuk produk ID ' . $item['id'] . '. Edit dibatalkan.'
                    );
                }

                $buyingPrice = $stock->unit_price;

                $transaction->transactionDetails()->create([
                    'product_id' => $item['id'],
                    'product_stock_id' => $stock->id,
                    'product_price' => $item['price'],
                    'buying_price' => $buyingPrice,
                    'quantity' => $item['qty'],
                    'total_price' => $item['price'] * $item['qty'],
                    'created_at' => $trxDate,
                    'updated_at' => $trxDate,
                ]);

                $stock->decrement('stock_opname', $item['qty']);
            }

            // 5. Update transaction header — only transaction_date changes; created_at stays as the original record creation timestamp.
            $transaction->update([
                'total_quantity' => $payload['totalQty'],
                'total_price' => $payload['totalAmount'],
                'discount' => $payload['discount'] ?? 0,
                'payment_method' => $payload['payment_method'],
                'is_paid' => (bool) $payload['is_paid'],
                'cash_received' => $payload['cash_received'] ?? null,
                'change_amount' => $payload['change_amount'] ?? null,
                'transaction_date' => $trxDate,
                'updated_at' => Carbon::now(),
            ]);

            // 6. Recompute invoice debts for each PRC invoice tied to this trx
            foreach ($transaction->invoices()->where('type', Invoice::TYPE_PURCHASE)->get() as $invoice) {
                $alreadyPaid = (float) DebtPaymentDetail::where('invoice_id', $invoice->id)
                    ->sum('amount_paid');

                if ($payload['payment_method'] !== 'Kredit') {
                    if ($alreadyPaid > 0) {
                        throw new RuntimeException(
                            'Tidak bisa ubah metode pembayaran: invoice ini sudah punya pembayaran utang. Hapus pembayaran utang dulu.'
                        );
                    }
                    $invoice->update(['debts' => 0]);
                } else {
                    $newDebt = (float) $payload['totalAmount'] - $alreadyPaid;
                    if ($newDebt < 0) {
                        throw new RuntimeException(
                            'Total transaksi baru lebih kecil dari pembayaran utang yang sudah dilakukan. Edit dibatalkan.'
                        );
                    }
                    $invoice->update(['debts' => $newDebt]);
                }
            }

            $after = $this->snapshotForLog($transaction->fresh(['transactionDetails', 'invoices']));

            activity('finance')
                ->causedBy(auth()->user())
                ->withProperties(['before' => $before, 'after' => $after])
                ->event('transaction_updated')
                ->log('Transaksi #' . $transaction->id . ' diperbarui');

            return $transaction->fresh(['transactionDetails', 'invoices']);
        });
    }

    /**
     * Restore stock for a collection of transaction details.
     * Prefers the original product_stock_id; falls back to a new ProductStock row.
     */
    private function reverseStockForDetails($details): void
    {
        foreach ($details as $detail) {
            if ($detail->product_stock_id) {
                $stock = ProductStock::find($detail->product_stock_id);
                if ($stock) {
                    $stock->increment('stock_opname', $detail->quantity);
                    continue;
                }
            }

            // Fallback: create a new batch with the recorded buying price.
            $nextBatch = (int) ProductStock::where('product_id', $detail->product_id)
                ->withTrashed()
                ->max('batch') + 1;

            $latest = ProductStock::where('product_id', $detail->product_id)
                ->orderByDesc('id')
                ->first();

            ProductStock::create([
                'product_id' => $detail->product_id,
                'batch' => $nextBatch,
                'stock_opname' => $detail->quantity,
                'unit_price' => $detail->buying_price,
                'price_consument' => $latest?->price_consument ?? 0,
                'price_r1' => $latest?->price_r1 ?? 0,
                'price_r2' => $latest?->price_r2 ?? 0,
                'expired_date' => $latest?->expired_date,
            ]);
        }
    }

    /**
     * Reverse a PRC invoice fully: undo any debt payments touching it,
     * delete CustomerProductPrice entries created uniquely by it, then delete the invoice.
     */
    private function reverseInvoiceFully(Invoice $invoice): void
    {
        if ($invoice->type !== Invoice::TYPE_PURCHASE) {
            // Defensive: a transaction shouldn't own a PAY invoice, but if it does, just delete.
            $invoice->delete();
            return;
        }

        $details = DebtPaymentDetail::where('invoice_id', $invoice->id)->get();

        foreach ($details as $detail) {
            $payment = DebtPayment::find($detail->debt_payment_id);

            $detail->delete();

            if (! $payment) {
                continue;
            }

            $remainingDetails = DebtPaymentDetail::where('debt_payment_id', $payment->id)->count();

            if ($remainingDetails === 0) {
                // Whole payment is now empty: drop it + its PAY receipt invoice.
                $receiptInvoiceId = $payment->payment_invoice_id;
                $payment->delete();
                if ($receiptInvoiceId) {
                    Invoice::where('id', $receiptInvoiceId)->delete();
                }
            } else {
                // Partially reduce the payment amount.
                $newAmount = max(0, (float) $payment->amount - (float) $detail->amount_paid);
                $payment->update(['amount' => $newAmount]);
            }
        }

        $invoice->delete();
    }

    private function snapshotForLog(Transaction $transaction): array
    {
        return [
            'transaction_id' => $transaction->id,
            'total_price' => (float) $transaction->total_price,
            'discount' => (float) $transaction->discount,
            'payment_method' => $transaction->payment_method,
            'is_paid' => (bool) $transaction->is_paid,
            'created_at' => optional($transaction->created_at)->toIso8601String(),
            'transaction_date' => optional($transaction->transaction_date)->toIso8601String(),
            'items' => $transaction->transactionDetails->map(fn($d) => [
                'product_id' => $d->product_id,
                'product_stock_id' => $d->product_stock_id,
                'quantity' => (int) $d->quantity,
                'product_price' => (float) $d->product_price,
                'buying_price' => (float) $d->buying_price,
                'total_price' => (float) $d->total_price,
            ])->toArray(),
            'invoices' => $transaction->invoices->map(fn($i) => [
                'id' => $i->id,
                'inv_code' => $i->inv_code,
                'debts' => (float) $i->debts,
                'type' => $i->type,
            ])->toArray(),
            'ip' => request()->ip(),
        ];
    }
}
