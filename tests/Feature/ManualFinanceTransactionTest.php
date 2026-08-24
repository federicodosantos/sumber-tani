<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerProductPrice;
use App\Models\Invoice;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjamin transaksi manual finance menghitung ulang total di sisi server
 * dengan presisi 3 desimal dan tidak mempercayai total dari browser.
 *
 * CATATAN: feature test ini membutuhkan migrate database; saat ini
 * terblokir oleh migration lama yang tidak kompatibel SQLite
 * (drop foreign key by name). Dapat dijalankan setelah blocker itu
 * dibereskan.
 */
class ManualFinanceTransactionTest extends TestCase
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

    private function makeR2Customer(): Customer
    {
        return Customer::create([
            'name' => 'Budi',
            'type' => 'r2',
            'phone_number' => '081234567890',
            'address' => 'Jl. Contoh',
        ]);
    }

    public function test_guest_manual_transaction_recalculates_total_with_decimal_quantity(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProductWithStock();

        $response = $this->post('/laporan-keuangan/manual', [
            'customer_kind' => 'guest',
            'reduce_stock' => 1,
            'items' => [
                ['id' => $productId, 'price' => 10000, 'qty' => 1.25, 'basePrice' => 10000],
            ],
            'totalQty' => 99,
            'totalAmount' => 99999,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => 1,
            'cash_received' => 20000,
            'change_amount' => 0,
            'created_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        $trx = Transaction::first();

        $this->assertSame('1.250', $trx->total_quantity);
        $this->assertSame('12500.000', $trx->total_price);

        $detail = $trx->transactionDetails->first();

        $this->assertSame('1.250', $detail->quantity);
        $this->assertSame('12500.000', $detail->total_price);
    }

    public function test_r2_credit_manual_transaction_stores_decimal_debt_and_custom_price(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProductWithStock();
        $customer = $this->makeR2Customer();

        $response = $this->post('/laporan-keuangan/manual', [
            'customer_kind' => 'r2',
            'customer_id' => $customer->id,
            'reduce_stock' => 0,
            'items' => [
                ['id' => $productId, 'price' => 10000.5, 'qty' => 1.25, 'basePrice' => 10000],
            ],
            'totalQty' => 1.25,
            'totalAmount' => 12500.625,
            'discount' => 0,
            'payment_method' => 'Kredit',
            'is_paid' => 0,
            'created_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        $trx = Transaction::first();
        $this->assertSame('12500.625', $trx->total_price);

        $invoice = Invoice::first();
        $this->assertSame('12500.625', $invoice->debts);

        $customPrice = CustomerProductPrice::first();
        $this->assertSame('10000.500', $customPrice->custom_price);
    }

    public function test_manual_transaction_rejects_more_than_three_decimal_quantity(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProductWithStock();

        $response = $this->post('/laporan-keuangan/manual', [
            'customer_kind' => 'guest',
            'reduce_stock' => 0,
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
}
