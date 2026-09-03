<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjamin alokasi stok desimal dialokasikan FIFO lintas batch dengan
 * lockForUpdate, tidak pernah menjadi negatif, dan reversal mengembalikan
 * ke batch yang benar.
 *
 * CATATAN: feature test ini membutuhkan migrate database; saat ini
 * terblokir oleh migration lama yang tidak kompatibel SQLite
 * (drop foreign key by name). Dapat dijalankan setelah blocker itu
 * dibereskan.
 */
class DecimalStockAllocationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsOwner(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'OWNER']));
    }

    private function makeProduct(): int
    {
        $category = ItemCategory::create(['name' => 'Kategori Test']);

        $product = Product::create([
            'code_id' => 'P-001',
            'name' => 'Produk Test',
            'item_category_id' => $category->id,
        ]);

        return $product->id;
    }

    private function makeBatch(int $productId, int $batch, float $qty, float $unitPrice): ProductStock
    {
        return ProductStock::create([
            'product_id' => $productId,
            'batch' => $batch,
            'stock_opname' => $qty,
            'unit_price' => $unitPrice,
            'price_consument' => 10000,
            'price_r1' => 9000,
            'price_r2' => 8000,
        ]);
    }

    private function checkout(int $productId, float $qty)
    {
        return $this->postJson('/checkout', [
            'items' => [['id' => $productId, 'price' => 10000, 'qty' => $qty, 'basePrice' => 10000]],
            'totalQty' => $qty,
            'totalAmount' => 10000 * $qty,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'cash_received' => 20000,
        ]);
    }

    public function test_checkout_allocates_decimal_quantity_across_batches(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();
        $batch1 = $this->makeBatch($productId, 1, 0.500, 5000);
        $batch2 = $this->makeBatch($productId, 2, 0.500, 6000);

        $response = $this->checkout($productId, 0.75);

        $response->assertOk()->assertJson(['success' => true]);

        $trx = Transaction::first();
        $this->assertSame('0.750', $trx->total_quantity);
        $this->assertSame('7500.000', $trx->total_price);

        $details = $trx->transactionDetails()->orderBy('product_stock_id')->get();
        $this->assertCount(2, $details);
        $this->assertSame('0.500', $details[0]->quantity);
        $this->assertSame('0.250', $details[1]->quantity);

        $this->assertSame('5000.000', $details[0]->buying_price);
        $this->assertSame('6000.000', $details[1]->buying_price);

        $this->assertSame('0.000', $batch1->fresh()->stock_opname);
        $this->assertSame('0.250', $batch2->fresh()->stock_opname);
    }

    public function test_checkout_rejects_when_total_stock_insufficient(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();
        $batch1 = $this->makeBatch($productId, 1, 0.500, 5000);

        $response = $this->checkout($productId, 0.75);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame('0.500', $batch1->fresh()->stock_opname);
    }

    public function test_reversal_restores_stock_to_original_batches(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();
        $batch1 = $this->makeBatch($productId, 1, 0.500, 5000);
        $batch2 = $this->makeBatch($productId, 2, 0.500, 6000);

        $this->checkout($productId, 0.75)->assertOk();

        $trx = Transaction::first();

        $response = $this->delete("/laporan-keuangan/{$trx->id}");

        $response->assertRedirect();

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame('0.500', $batch1->fresh()->stock_opname);
        $this->assertSame('0.500', $batch2->fresh()->stock_opname);
    }
}
