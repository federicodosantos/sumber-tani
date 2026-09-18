<?php

namespace Tests\Feature;

use App\Models\ProductPurchase;
use App\Models\ProductPurchaseReceipt;
use App\Models\ProductStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPurchases;
use Tests\TestCase;

/**
 * Hapus nota harus ikut membalikkan stok yang lahir darinya.
 *
 * Perilaku lama hanya menghapus header + cascade details, stok dibiarkan —
 * nota hilang tapi barangnya tetap tercatat ada di gudang.
 */
class PurchaseDeleteWithReceiptsTest extends TestCase
{
    use BuildsPurchases;
    use RefreshDatabase;

    public function test_deleting_a_nota_reverses_its_stock_when_batches_are_intact(): void
    {
        $this->actingAsOwner();
        $urea = $this->makeProduct('P-001');
        $npk = $this->makeProduct('P-002');

        $purchase = $this->createPurchase([
            $this->line($urea, '40.000'),
            $this->line($npk, '50.000'),
        ]);

        $this->delete('/purchase/'.$purchase->id)->assertRedirect();

        $this->assertNull(ProductPurchase::find($purchase->id));
        $this->assertSame(0, ProductPurchaseReceipt::count());
        $this->assertSame(0, ProductStock::where('product_id', $urea)->count());
        $this->assertSame(0, ProductStock::where('product_id', $npk)->count());
    }

    public function test_deleting_is_blocked_when_a_batch_was_already_consumed(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([$this->line($productId, '40.000')]);

        // Simulasikan 15 sudah terjual lewat kasir.
        ProductStock::where('product_id', $productId)->update(['stock_opname' => '25.000']);

        $this->delete('/purchase/'.$purchase->id)->assertSessionHasErrors();

        $this->assertNotNull(ProductPurchase::find($purchase->id));
        $this->assertSame(1, ProductStock::where('product_id', $productId)->count());
    }

    public function test_pending_lines_alone_delete_cleanly(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([$this->line($productId, '100.000', directStock: false)]);

        $this->delete('/purchase/'.$purchase->id)->assertRedirect();

        $this->assertNull(ProductPurchase::find($purchase->id));
        $this->assertSame(0, ProductStock::where('product_id', $productId)->count());
    }

    public function test_delete_preview_reports_the_stock_impact(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();
        $purchase = $this->createPurchase([$this->line($productId, '40.000')]);

        $this->getJson('/purchase/'.$purchase->id.'/hapus/pratinjau')
            ->assertOk()
            ->assertJson([
                'can_delete' => true,
                'impact' => [
                    ['product_name' => 'Produk P-001', 'quantity' => '40.000'],
                ],
                'blockers' => [],
            ]);
    }

    public function test_delete_preview_reports_blockers(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();
        $purchase = $this->createPurchase([$this->line($productId, '40.000')]);

        ProductStock::where('product_id', $productId)->update(['stock_opname' => '25.000']);

        $response = $this->getJson('/purchase/'.$purchase->id.'/hapus/pratinjau')->assertOk();

        $response->assertJson(['can_delete' => false]);
        $this->assertNotEmpty($response->json('blockers'));
    }
}
