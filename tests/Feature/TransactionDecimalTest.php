<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjamin checkout reguler menghitung ulang total di sisi server dengan
 * presisi 3 desimal, tidak mempercayai total dari browser, dan tetap
 * menjaga sinkronisasi transaksi offline.
 *
 * CATATAN: feature test ini membutuhkan migrate database; saat ini
 * terblokir oleh migration lama yang tidak kompatibel SQLite
 * (drop foreign key by name). Dapat dijalankan setelah blocker itu
 * dibereskan.
 */
class TransactionDecimalTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsOwner(): void
    {
        $this->actingAs(User::factory()->create());
    }

    private function makeProductWithStock(float $qty = 10): int
    {
        $category = ItemCategory::create(['name' => 'Kategori Test']);

        $product = Product::create([
            'code_id' => 'P-001',
            'name' => 'Produk Test',
            'item_category_id' => $category->id,
        ]);

        ProductStock::create([
            'product_id' => $product->id,
            'batch' => 1,
            'stock_opname' => $qty,
            'unit_price' => 5000,
            'price_consument' => 10000,
            'price_r1' => 9000,
            'price_r2' => 8000,
        ]);

        return $product->id;
    }

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'name' => 'Budi',
            'type' => 'r2',
            'phone_number' => '081234567890',
            'address' => 'Jl. Contoh',
        ]);
    }

    public function test_checkout_recalculates_total_with_decimal_quantity(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProductWithStock();

        $response = $this->postJson('/checkout', [
            'items' => [['id' => $productId, 'price' => 10000, 'qty' => 1.25, 'basePrice' => 10000]],
            'totalQty' => 99,
            'totalAmount' => 99999,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'cash_received' => 20000,
            'change_amount' => 0,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $trx = Transaction::first();

        $this->assertSame('1.250', $trx->total_quantity);
        $this->assertSame('12500.000', $trx->total_price);

        $detail = $trx->transactionDetails->first();

        $this->assertSame('10000.000', $detail->product_price);
        $this->assertSame('1.250', $detail->quantity);
        $this->assertSame('12500.000', $detail->total_price);
    }

    public function test_checkout_accepts_comma_decimal_input(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProductWithStock();

        $response = $this->postJson('/checkout', [
            'items' => [['id' => $productId, 'price' => '10000', 'qty' => '1,250', 'basePrice' => '10000']],
            'totalQty' => '99',
            'totalAmount' => '99999',
            'discount' => '0',
            'payment_method' => 'Cash',
            'is_paid' => true,
            'cash_received' => '20000',
            'change_amount' => '0',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $trx = Transaction::first();

        $this->assertSame('1.250', $trx->total_quantity);
        $this->assertSame('12500.000', $trx->total_price);
    }

    public function test_checkout_rejects_quantity_with_more_than_three_decimals(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProductWithStock();

        $response = $this->postJson('/checkout', [
            'items' => [['id' => $productId, 'price' => 10000, 'qty' => 1.1234, 'basePrice' => 10000]],
            'totalQty' => 1.1234,
            'totalAmount' => 11234,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'cash_received' => 20000,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_credit_checkout_stores_invoice_debt_with_three_decimals(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProductWithStock();
        $customer = $this->makeCustomer();

        $response = $this->postJson('/checkout', [
            'items' => [['id' => $productId, 'price' => 10000.5, 'qty' => 1.25, 'basePrice' => 10000.5]],
            'totalQty' => 1.25,
            'totalAmount' => 12500.625,
            'discount' => 0,
            'payment_method' => 'Kredit',
            'is_paid' => false,
            'customer_id' => $customer->id,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $invoice = Invoice::first();

        $this->assertSame('12500.625', $invoice->debts);
    }

    public function test_checkout_rejects_insufficient_cash(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProductWithStock();

        $response = $this->postJson('/checkout', [
            'items' => [['id' => $productId, 'price' => 10000, 'qty' => 1, 'basePrice' => 10000]],
            'totalQty' => 1,
            'totalAmount' => 10000,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'cash_received' => 5000,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_offline_transaction_is_idempotent(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProductWithStock();

        $payload = [
            'items' => [['id' => $productId, 'price' => 10000, 'qty' => 1.25, 'basePrice' => 10000]],
            'totalQty' => 1.25,
            'totalAmount' => 12500,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'cash_received' => 20000,
            'offline_uuid' => 'offline-abc-123',
        ];

        $this->postJson('/checkout', $payload)->assertOk()->assertJson(['success' => true]);

        $duplicate = $this->postJson('/checkout', $payload);

        $duplicate->assertOk()->assertJson(['success' => false]);
        $this->assertDatabaseCount('transactions', 1);
    }
}
