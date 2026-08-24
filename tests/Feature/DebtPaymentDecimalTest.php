<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DebtPayment;
use App\Models\DebtPaymentDetail;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjamin pembayaran hutang (FIFO) mempertahankan presisi 3 desimal pada
 * alokasi, saldo kredit, dan kelebihan bayar.
 *
 * CATATAN: feature test ini membutuhkan migrate database; saat ini
 * terblokir oleh migration lama yang tidak kompatibel SQLite
 * (drop foreign key by name). Dapat dijalankan setelah blocker itu
 * dibereskan.
 */
class DebtPaymentDecimalTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsOwner(): void
    {
        $this->actingAs(User::factory()->create());
    }

    private function makeR2Customer(float $creditBalance = 0): Customer
    {
        $customer = Customer::create([
            'name' => 'Budi',
            'type' => 'r2',
            'phone_number' => '081234567890',
            'address' => 'Jl. Contoh',
            'credit_balance' => $creditBalance,
        ]);

        return $customer;
    }

    private function makeDebt(Customer $customer, float $debts): Invoice
    {
        return Invoice::create([
            'customer_id' => $customer->id,
            'transaction_id' => null,
            'debts' => $debts,
            'type' => Invoice::TYPE_PURCHASE,
            'inv_code' => 'TEST-'.uniqid(),
        ]);
    }

    private function pay(Customer $customer, array $payload)
    {
        return $this->post("/customer-r2/{$customer->id}/pay", array_merge([
            'payment_method' => 'Cash',
            'payment_date' => now()->format('Y-m-d'),
        ], $payload));
    }

    public function test_exact_decimal_payment_clears_debt(): void
    {
        $this->actingAsOwner();
        $customer = $this->makeR2Customer();
        $invoice = $this->makeDebt($customer, 100.125);

        $response = $this->pay($customer, ['amount' => 100.125]);

        $response->assertRedirect();

        $this->assertSame('0.000', $invoice->fresh()->debts);

        $payment = DebtPayment::first();
        $this->assertSame('100.125', $payment->amount);

        $detail = DebtPaymentDetail::first();
        $this->assertSame('100.125', $detail->amount_paid);
        $this->assertSame('100.125', $detail->debt_before);
        $this->assertSame('0.000', $detail->debt_after);
    }

    public function test_partial_decimal_payment_leaves_decimal_remainder(): void
    {
        $this->actingAsOwner();
        $customer = $this->makeR2Customer();
        $invoice = $this->makeDebt($customer, 100.125);

        $response = $this->pay($customer, ['amount' => 50.125]);

        $response->assertRedirect();

        $this->assertSame('50.000', $invoice->fresh()->debts);
    }

    public function test_fifo_distributes_across_multiple_decimal_invoices(): void
    {
        $this->actingAsOwner();
        $customer = $this->makeR2Customer();
        $invoiceA = $this->makeDebt($customer, 100.125);
        $invoiceB = $this->makeDebt($customer, 50.000);

        $response = $this->pay($customer, ['amount' => 125.125]);

        $response->assertRedirect();

        $this->assertSame('0.000', $invoiceA->fresh()->debts);
        $this->assertSame('25.000', $invoiceB->fresh()->debts);
        $this->assertDatabaseCount('debt_payment_details', 2);
    }

    public function test_overpayment_saved_as_credit_with_decimal(): void
    {
        $this->actingAsOwner();
        $customer = $this->makeR2Customer();
        $invoice = $this->makeDebt($customer, 100.125);

        $response = $this->pay($customer, ['amount' => 120.125, 'overpayment_action' => 'credit']);

        $response->assertRedirect();

        $this->assertSame('0.000', $invoice->fresh()->debts);

        $payment = DebtPayment::first();
        $this->assertSame('20.000', $payment->credit_amount);
        $this->assertSame('0.000', $payment->refund_amount);

        $this->assertSame('20.000', $customer->fresh()->credit_balance);
    }

    public function test_overpayment_recorded_as_refund_with_decimal(): void
    {
        $this->actingAsOwner();
        $customer = $this->makeR2Customer();
        $invoice = $this->makeDebt($customer, 100.125);

        $response = $this->pay($customer, ['amount' => 120.125, 'overpayment_action' => 'refund']);

        $response->assertRedirect();

        $payment = DebtPayment::first();
        $this->assertSame('20.000', $payment->refund_amount);
        $this->assertSame('0.000', $payment->credit_amount);

        $this->assertSame('0.000', $customer->fresh()->credit_balance);
    }

    public function test_credit_balance_is_deducted_with_decimal(): void
    {
        $this->actingAsOwner();
        $customer = $this->makeR2Customer(50.000);
        $invoice = $this->makeDebt($customer, 100.125);

        $response = $this->pay($customer, [
            'amount' => 50.000,
            'use_credit_amount' => 50.000,
        ]);

        $response->assertRedirect();

        $this->assertSame('0.125', $invoice->fresh()->debts);
        $this->assertSame('0.000', $customer->fresh()->credit_balance);

        $payment = DebtPayment::first();
        $this->assertSame('50.000', $payment->credit_used);
    }
}
