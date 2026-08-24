<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerProductPrice;
use App\Models\Invoice;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjamin manual invoice pelanggan mendukung quantity desimal, menghitung
 * ulang total di sisi server, dan tidak mempercayai total dari browser.
 *
 * CATATAN: feature test ini membutuhkan migrate database; saat ini
 * terblokir oleh migration lama yang tidak kompatibel SQLite
 * (drop foreign key by name). Dapat dijalankan setelah blocker itu
 * dibereskan.
 */
class CustomerInvoiceDecimalTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsOwner(): void
    {
        $this->actingAs(User::factory()->create());
    }

    private function makeProduct(string $code, string $name, float $basePrice = 10000): int
    {
        $category = ItemCategory::create(['name' => 'Kategori Test']);

        $product = Product::create([
            'code_id' => $code,
            'name' => $name,
            'item_category_id' => $category->id,
        ]);

        return $product->id;
    }

    private function makeR2Customer(): Customer
    {
        return Customer::create([
            'name' => 'Budi',
            'type' => 'r2',
            'phone_number' => '081234567890',
            'address' => 'Jl. Contoh',
        ]);
    }

    public function test_invoice_recalculates_totals_with_decimal_quantity_and_custom_price(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct('P-001', 'Produk A');
        $customer = $this->makeR2Customer();

        $response = $this->post("/customer-r2/{$customer->id}/invoice", [
            'items' => [
                ['id' => $productId, 'price' => 10000.5, 'qty' => 1.25, 'basePrice' => 10000],
            ],
            'totalQty' => 99,
            'totalAmount' => 99999,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => 1,
            'cash_received' => 20000,
            'created_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        $trx = Transaction::first();
        $this->assertSame('1.250', $trx->total_quantity);
        $this->assertSame('12500.625', $trx->total_price);

        $detail = $trx->transactionDetails->first();
        $this->assertSame('1.250', $detail->quantity);
        $this->assertSame('12500.625', $detail->total_price);

        $customPrice = CustomerProductPrice::first();
        $this->assertSame('10000.500', $customPrice->custom_price);
    }

    public function test_invoice_sums_multiple_decimal_quantities(): void
    {
        $this->actingAsOwner();
        $productA = $this->makeProduct('P-001', 'Produk A');
        $productB = $this->makeProduct('P-002', 'Produk B');
        $customer = $this->makeR2Customer();

        $response = $this->post("/customer-r2/{$customer->id}/invoice", [
            'items' => [
                ['id' => $productA, 'price' => 10000, 'qty' => 0.125, 'basePrice' => 10000],
                ['id' => $productB, 'price' => 2000, 'qty' => 0.5, 'basePrice' => 2000],
            ],
            'totalQty' => 9,
            'totalAmount' => 99999,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => 1,
            'cash_received' => 5000,
            'created_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        $trx = Transaction::first();
        $this->assertSame('0.625', $trx->total_quantity);
        $this->assertSame('2250.000', $trx->total_price);
    }

    public function test_invoice_rejects_more_than_three_decimal_quantity(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct('P-001', 'Produk A');
        $customer = $this->makeR2Customer();

        $response = $this->post("/customer-r2/{$customer->id}/invoice", [
            'items' => [
                ['id' => $productId, 'price' => 10000, 'qty' => 1.1234, 'basePrice' => 10000],
            ],
            'totalQty' => 1.1234,
            'totalAmount' => 11234,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => 1,
            'cash_received' => 20000,
            'created_at' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('items.0.qty');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_credit_invoice_stores_decimal_debt_and_custom_price(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct('P-001', 'Produk A');
        $customer = $this->makeR2Customer();

        $response = $this->post("/customer-r2/{$customer->id}/invoice", [
            'items' => [
                ['id' => $productId, 'price' => 12500.125, 'qty' => 1, 'basePrice' => 10000],
            ],
            'totalQty' => 1,
            'totalAmount' => 12500.125,
            'discount' => 0,
            'payment_method' => 'Kredit',
            'is_paid' => 0,
            'created_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        $invoice = Invoice::first();
        $this->assertSame('12500.125', $invoice->debts);

        $customPrice = CustomerProductPrice::first();
        $this->assertSame('12500.125', $customPrice->custom_price);
    }
}
