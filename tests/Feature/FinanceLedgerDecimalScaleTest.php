<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerProductPrice;
use App\Models\DebtPayment;
use App\Models\DebtPaymentDetail;
use App\Models\Invoice;
use App\Models\ItemCategory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjamin kolom ledger finance (invoice, pembayaran, detail alokasi,
 * saldo kredit, harga khusus pelanggan) menyimpan dan membaca kembali
 * nilai dengan presisi 3 desimal.
 *
 * Catatan: pada SQLite test nilai selalu disimpan penuh (numeric),
 * sehingga test ini berfungsi sebagai guardrail perilaku + jaminan
 * migration berjalan. Enforce skala sesungguhnya terjadi di MySQL.
 */
class FinanceLedgerDecimalScaleTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'name' => 'Budi',
            'type' => 'r2',
            'phone_number' => '081234567890',
            'address' => 'Jl. Contoh',
        ]);
    }

    private function makeInvoice(Customer $customer, float $debts): Invoice
    {
        return Invoice::create([
            'customer_id' => $customer->id,
            'transaction_id' => null,
            'debts' => $debts,
            'type' => Invoice::TYPE_PURCHASE,
            'inv_code' => 'TEST-001',
        ]);
    }

    private function makeProduct(): Product
    {
        $category = ItemCategory::create(['name' => 'Test Kategori']);

        return Product::create([
            'code_id' => 'TST-001',
            'name' => 'Produk Test',
            'item_category_id' => $category->id,
        ]);
    }

    public function test_invoice_debts_round_trips_with_three_decimal_places(): void
    {
        $customer = $this->makeCustomer();

        $invoice = $this->makeInvoice($customer, 100.125);

        $this->assertSame('100.125', $invoice->fresh()->debts);
    }

    public function test_existing_two_decimal_value_is_preserved_as_three_decimal(): void
    {
        $customer = $this->makeCustomer();

        $invoice = $this->makeInvoice($customer, 100.25);

        $this->assertSame('100.250', $invoice->fresh()->debts);
    }

    public function test_debt_payment_amounts_round_trip_with_three_decimal_places(): void
    {
        $customer = $this->makeCustomer();

        $payment = DebtPayment::create([
            'customer_id' => $customer->id,
            'amount' => 50.125,
            'payment_method' => 'Cash',
            'credit_amount' => 0.005,
            'refund_amount' => 0.001,
            'credit_used' => 1.5,
        ]);

        $fresh = $payment->fresh();

        $this->assertSame('50.125', $fresh->amount);
        $this->assertSame('0.005', $fresh->credit_amount);
        $this->assertSame('0.001', $fresh->refund_amount);
        $this->assertSame('1.500', $fresh->credit_used);
    }

    public function test_debt_payment_detail_allocations_round_trip_with_three_decimal_places(): void
    {
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer, 100.125);
        $payment = DebtPayment::create([
            'customer_id' => $customer->id,
            'amount' => 25.125,
            'payment_method' => 'Cash',
        ]);

        $detail = DebtPaymentDetail::create([
            'debt_payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount_paid' => 25.125,
            'debt_before' => 100.125,
            'debt_after' => 75.0,
        ]);

        $fresh = $detail->fresh();

        $this->assertSame('25.125', $fresh->amount_paid);
        $this->assertSame('100.125', $fresh->debt_before);
        $this->assertSame('75.000', $fresh->debt_after);
    }

    public function test_customer_credit_balance_round_trips_with_three_decimal_places(): void
    {
        $customer = $this->makeCustomer();

        $customer->update(['credit_balance' => 12.345]);

        $this->assertSame('12.345', $customer->fresh()->credit_balance);
    }

    public function test_custom_price_round_trips_with_three_decimal_places(): void
    {
        $customer = $this->makeCustomer();
        $product = $this->makeProduct();

        $price = CustomerProductPrice::create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'custom_price' => 12500.125,
        ]);

        $this->assertSame('12500.125', $price->fresh()->custom_price);
    }
}
