<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DebtPayment;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjamin edit & delete pembayaran hutang melakukan rollback dengan presisi
 * 3 desimal, memulihkan saldo kredit, dan menolak nominal melebihi hutang
 * di sisi server.
 *
 * CATATAN: feature test ini membutuhkan migrate database; saat ini
 * terblokir oleh migration lama yang tidak kompatibel SQLite
 * (drop foreign key by name). Dapat dijalankan setelah blocker itu
 * dibereskan.
 */
class DebtPaymentRollbackTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsOwner(): void
    {
        $this->actingAs(User::factory()->create());
    }

    private function makeR2Customer(float $creditBalance = 0): Customer
    {
        return Customer::create([
            'name' => 'Budi',
            'type' => 'r2',
            'phone_number' => '081234567890',
            'address' => 'Jl. Contoh',
            'credit_balance' => $creditBalance,
        ]);
    }

    private function makeDebtInvoice(Customer $customer, float $debts, ?Transaction $trx = null): Invoice
    {
        return Invoice::create([
            'customer_id' => $customer->id,
            'transaction_id' => $trx?->id,
            'debts' => $debts,
            'type' => Invoice::TYPE_PURCHASE,
            'inv_code' => 'TEST-'.uniqid(),
        ]);
    }

    private function makeTransaction(bool $isPaid = false): Transaction
    {
        return Transaction::create([
            'total_quantity' => 1.000,
            'total_price' => 100.125,
            'discount' => 0,
            'payment_method' => 'Kredit',
            'is_paid' => $isPaid,
            'transaction_date' => now(),
        ]);
    }

    private function pay(Customer $customer, array $payload): void
    {
        $this->post("/customer-r2/{$customer->id}/pay", array_merge([
            'payment_method' => 'Cash',
            'payment_date' => now()->format('Y-m-d'),
        ], $payload))->assertRedirect();
    }

    private function payInvoiceOf(Invoice $debtInvoice): Invoice
    {
        return Invoice::where('type', Invoice::TYPE_DEBT_PAYMENT)->first();
    }

    public function test_edit_payment_reallocates_with_decimal_precision(): void
    {
        $this->actingAsOwner();
        $customer = $this->makeR2Customer();
        $debtInvoice = $this->makeDebtInvoice($customer, 100.125);

        $this->pay($customer, ['amount' => 50.125]);
        $payInvoice = $this->payInvoiceOf($debtInvoice);

        $response = $this->put("/customer-r2/debt-payment/{$payInvoice->id}", [
            'amount' => 25.125,
            'payment_method' => 'Cash',
            'created_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        $this->assertSame('75.000', $debtInvoice->fresh()->debts);

        $payment = DebtPayment::first();
        $this->assertSame('25.125', $payment->amount);

        $this->assertSame('25.125', $payment->details->first()->amount_paid);
    }

    public function test_edit_payment_exceeding_debt_is_rejected_server_side(): void
    {
        $this->actingAsOwner();
        $customer = $this->makeR2Customer();
        $debtInvoice = $this->makeDebtInvoice($customer, 100.125);

        $this->pay($customer, ['amount' => 50.125]);
        $payInvoice = $this->payInvoiceOf($debtInvoice);

        $response = $this->put("/customer-r2/debt-payment/{$payInvoice->id}", [
            'amount' => 200,
            'payment_method' => 'Cash',
            'created_at' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('general');
        $this->assertSame('50.000', $debtInvoice->fresh()->debts);
        $this->assertSame('50.125', DebtPayment::first()->amount);
    }

    public function test_delete_payment_restores_debt(): void
    {
        $this->actingAsOwner();
        $customer = $this->makeR2Customer();
        $debtInvoice = $this->makeDebtInvoice($customer, 100.125);

        $this->pay($customer, ['amount' => 50.125]);
        $payInvoice = $this->payInvoiceOf($debtInvoice);

        $response = $this->delete("/customer-r2/debt-payment/{$payInvoice->id}");

        $response->assertRedirect();

        $this->assertSame('100.125', $debtInvoice->fresh()->debts);
        $this->assertDatabaseCount('debt_payments', 0);
        $this->assertDatabaseMissing('invoices', ['id' => $payInvoice->id]);
    }

    public function test_delete_payment_restores_used_credit(): void
    {
        $this->actingAsOwner();
        $customer = $this->makeR2Customer(50.000);
        $debtInvoice = $this->makeDebtInvoice($customer, 100.125);

        $this->pay($customer, ['amount' => 50.000, 'use_credit_amount' => 50.000]);
        $payInvoice = $this->payInvoiceOf($debtInvoice);

        $this->assertSame('0.125', $debtInvoice->fresh()->debts);
        $this->assertSame('0.000', $customer->fresh()->credit_balance);

        $this->delete("/customer-r2/debt-payment/{$payInvoice->id}")->assertRedirect();

        $this->assertSame('100.125', $debtInvoice->fresh()->debts);
        $this->assertSame('50.000', $customer->fresh()->credit_balance);
    }

    public function test_delete_payment_reverses_saved_credit(): void
    {
        $this->actingAsOwner();
        $customer = $this->makeR2Customer();
        $debtInvoice = $this->makeDebtInvoice($customer, 100.125);

        $this->pay($customer, ['amount' => 120.125, 'overpayment_action' => 'credit']);
        $payInvoice = $this->payInvoiceOf($debtInvoice);

        $this->assertSame('20.000', $customer->fresh()->credit_balance);

        $this->delete("/customer-r2/debt-payment/{$payInvoice->id}")->assertRedirect();

        $this->assertSame('100.125', $debtInvoice->fresh()->debts);
        $this->assertSame('0.000', $customer->fresh()->credit_balance);
    }

    public function test_delete_payment_restores_transaction_paid_status(): void
    {
        $this->actingAsOwner();
        $customer = $this->makeR2Customer();
        $trx = $this->makeTransaction(false);
        $debtInvoice = $this->makeDebtInvoice($customer, 100.125, $trx);

        $this->pay($customer, ['amount' => 100.125]);
        $payInvoice = $this->payInvoiceOf($debtInvoice);

        $this->assertTrue((bool) $trx->fresh()->is_paid);

        $this->delete("/customer-r2/debt-payment/{$payInvoice->id}")->assertRedirect();

        $this->assertFalse((bool) $trx->fresh()->is_paid);
    }
}
