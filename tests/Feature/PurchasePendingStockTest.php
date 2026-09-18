<?php

namespace Tests\Feature;

use App\Models\ProductStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPurchases;
use Tests\TestCase;

/**
 * Pilihan "langsung masuk stok" per baris item saat nota disimpan.
 *
 * Baris direct: batch dibuat seketika (perilaku lama, harus tetap sama).
 * Baris pending: tidak ada batch sama sekali sampai barang diterima.
 */
class PurchasePendingStockTest extends TestCase
{
    use BuildsPurchases;
    use RefreshDatabase;

    public function test_pending_line_creates_no_stock_batch(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([
            $this->line($productId, '100.000', directStock: false),
        ]);

        $this->assertSame(0, ProductStock::where('product_id', $productId)->count());

        $detail = $this->detailOf($purchase);
        $this->assertSame('0.000', $detail->received_quantity);
        $this->assertSame('pending', $detail->receipt_mode);
    }

    public function test_direct_line_creates_batch_and_counts_as_fully_received(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([
            $this->line($productId, '40.000', directStock: true),
        ]);

        $batches = ProductStock::where('product_id', $productId)->get();
        $this->assertCount(1, $batches);
        $this->assertSame('40.000', $batches[0]->stock_opname);

        $detail = $this->detailOf($purchase);
        $this->assertSame('40.000', $detail->received_quantity);
        $this->assertSame('direct', $detail->receipt_mode);
    }

    public function test_line_without_direct_stock_key_keeps_legacy_behaviour(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([
            $this->line($productId, '25.000', directStock: null),
        ]);

        $this->assertSame(1, ProductStock::where('product_id', $productId)->count());
        $this->assertSame('25.000', $this->detailOf($purchase)->received_quantity);
    }

    public function test_mixed_nota_only_creates_batch_for_direct_lines(): void
    {
        $this->actingAsOwner();
        $urea = $this->makeProduct('P-001');
        $npk = $this->makeProduct('P-002');

        $this->createPurchase([
            $this->line($urea, '100.000', directStock: true),
            $this->line($npk, '50.000', directStock: false),
        ]);

        $this->assertSame(1, ProductStock::where('product_id', $urea)->count());
        $this->assertSame(0, ProductStock::where('product_id', $npk)->count());
    }
}
