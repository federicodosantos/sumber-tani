<?php

namespace Tests\Feature;

use App\Models\ProductPurchaseDetail;
use App\Models\ProductPurchaseReceipt;
use App\Models\ProductStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPurchases;
use Tests\TestCase;

/**
 * Edit nota harus merekonsiliasi baris per id, bukan delete-recreate.
 *
 * Perilaku lama (details()->delete() + recreate) akan menghapus seluruh
 * riwayat penerimaan lewat cascade FK — satu kali edit nota melenyapkan
 * jejak barang yang sudah masuk gudang.
 */
class PurchaseEditWithReceiptsTest extends TestCase
{
    use BuildsPurchases;
    use RefreshDatabase;

    private function update(int $purchaseId, array $lines)
    {
        return $this->put('/purchase/'.$purchaseId, $this->payload($lines));
    }

    public function test_editing_a_nota_keeps_existing_receipts_and_batches(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();
        $purchase = $this->createPurchase([$this->line($productId, '100.000', directStock: false)]);
        $detail = $this->detailOf($purchase);

        $this->post('/penerimaan/'.$detail->id.'/terima', [
            'quantity' => '40', 'received_date' => now()->toDateString(),
        ]);

        $this->update($purchase->id, [
            $this->line($productId, '100.000', directStock: null, id: $detail->id),
        ])->assertRedirect();

        $this->assertSame(1, ProductPurchaseReceipt::count());
        $this->assertSame(1, ProductStock::where('product_id', $productId)->count());

        $detail->refresh();
        $this->assertSame('40.000', $detail->received_quantity);
        $this->assertSame(ProductPurchaseDetail::STATUS_PARTIAL, $detail->receipt_status);
    }

    public function test_detail_id_is_stable_after_edit(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();
        $purchase = $this->createPurchase([$this->line($productId, '10.000')]);
        $originalId = $this->detailOf($purchase)->id;

        $this->update($purchase->id, [
            $this->line($productId, '12.000', directStock: null, id: $originalId),
        ])->assertRedirect();

        $this->assertSame($originalId, $this->detailOf($purchase->fresh())->id);
        $this->assertSame('12.000', $this->detailOf($purchase->fresh())->quantity);
    }

    public function test_reducing_quantity_below_received_is_rejected(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();
        $purchase = $this->createPurchase([$this->line($productId, '100.000', directStock: false)]);
        $detail = $this->detailOf($purchase);

        $this->post('/penerimaan/'.$detail->id.'/terima', [
            'quantity' => '40', 'received_date' => now()->toDateString(),
        ]);

        $this->update($purchase->id, [
            $this->line($productId, '30.000', directStock: null, id: $detail->id),
        ])->assertSessionHasErrors();

        $detail->refresh();
        $this->assertSame('100.000', $detail->quantity);
    }

    public function test_removing_a_received_line_is_rejected(): void
    {
        $this->actingAsOwner();
        $keep = $this->makeProduct('P-001');
        $drop = $this->makeProduct('P-002');

        $purchase = $this->createPurchase([
            $this->line($keep, '10.000'),
            $this->line($drop, '20.000'),
        ]);

        $keepDetail = $this->detailOf($purchase, 0);

        $this->update($purchase->id, [
            $this->line($keep, '10.000', directStock: null, id: $keepDetail->id),
        ])->assertSessionHasErrors();

        $this->assertSame(2, $purchase->details()->count());
    }

    public function test_removing_an_unreceived_line_succeeds(): void
    {
        $this->actingAsOwner();
        $keep = $this->makeProduct('P-001');
        $drop = $this->makeProduct('P-002');

        $purchase = $this->createPurchase([
            $this->line($keep, '10.000', directStock: true),
            $this->line($drop, '20.000', directStock: false),
        ]);

        $keepDetail = $this->detailOf($purchase, 0);

        $this->update($purchase->id, [
            $this->line($keep, '10.000', directStock: null, id: $keepDetail->id),
        ])->assertRedirect();

        $this->assertSame(1, $purchase->details()->count());
    }

    public function test_changing_product_on_a_received_line_is_rejected(): void
    {
        $this->actingAsOwner();
        $original = $this->makeProduct('P-001');
        $other = $this->makeProduct('P-002');

        $purchase = $this->createPurchase([$this->line($original, '10.000')]);
        $detail = $this->detailOf($purchase);

        $this->update($purchase->id, [
            $this->line($other, '10.000', directStock: null, id: $detail->id),
        ])->assertSessionHasErrors();

        $detail->refresh();
        $this->assertSame($original, $detail->product_id);
    }

    public function test_price_change_propagates_to_batches_of_that_line(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([
            $this->line($productId, '10.000', hetPrice: '50000.000'),
        ]);
        $detail = $this->detailOf($purchase);

        $batch = ProductStock::where('product_id', $productId)->firstOrFail();
        $this->assertSame('50000.000', $batch->unit_price);

        $this->update($purchase->id, [
            $this->line($productId, '10.000', directStock: null, id: $detail->id, hetPrice: '52000.000'),
        ])->assertRedirect();

        $this->assertSame('52000.000', $batch->fresh()->unit_price);
        $this->assertSame('52000.000', ProductPurchaseReceipt::firstOrFail()->unit_price);
    }

    public function test_new_pending_line_can_be_added_during_edit(): void
    {
        $this->actingAsOwner();
        $existing = $this->makeProduct('P-001');
        $added = $this->makeProduct('P-002');

        $purchase = $this->createPurchase([$this->line($existing, '10.000')]);
        $detail = $this->detailOf($purchase);

        $this->update($purchase->id, [
            $this->line($existing, '10.000', directStock: null, id: $detail->id),
            $this->line($added, '50.000', directStock: false),
        ])->assertRedirect();

        $this->assertSame(2, $purchase->details()->count());
        $this->assertSame(0, ProductStock::where('product_id', $added)->count());
    }
}
