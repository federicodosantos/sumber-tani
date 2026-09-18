<?php

namespace Tests\Feature;

use App\Models\ProductPurchaseDetail;
use App\Models\ProductPurchaseReceipt;
use App\Models\ProductStock;
use App\Services\ProductStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPurchases;
use Tests\TestCase;

/**
 * Penerimaan barang bertahap untuk satu baris item nota.
 *
 * Contoh acuan dari user: beli 100 karung dalam satu baris, datang 40 dulu,
 * sisanya 60 menyusul.
 */
class GoodsReceiptTest extends TestCase
{
    use BuildsPurchases;
    use RefreshDatabase;

    private function pendingDetail(string $qty = '100.000', ?string $expired = null): ProductPurchaseDetail
    {
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([
            $this->line($productId, $qty, directStock: false, expiredDate: $expired),
        ]);

        return $this->detailOf($purchase);
    }

    private function receive(ProductPurchaseDetail $detail, string $qty, array $extra = [])
    {
        return $this->post('/penerimaan/'.$detail->id.'/terima', array_merge([
            'quantity' => $qty,
            'received_date' => now()->toDateString(),
        ], $extra));
    }

    public function test_partial_receipt_adds_only_received_quantity_to_stock(): void
    {
        $this->actingAsOwner();
        $detail = $this->pendingDetail('100.000');

        $this->receive($detail, '40')->assertRedirect();

        $batches = ProductStock::where('product_id', $detail->product_id)->get();
        $this->assertCount(1, $batches);
        $this->assertSame('40.000', $batches[0]->stock_opname);

        $detail->refresh();
        $this->assertSame('40.000', $detail->received_quantity);
        $this->assertSame('60.000', $detail->outstanding_quantity);
        $this->assertSame(ProductPurchaseDetail::STATUS_PARTIAL, $detail->receipt_status);
    }

    public function test_second_receipt_completes_the_line_as_a_separate_batch(): void
    {
        $this->actingAsOwner();
        $detail = $this->pendingDetail('100.000');

        $this->receive($detail, '40');
        $this->receive($detail, '60');

        $batches = ProductStock::where('product_id', $detail->product_id)
            ->orderBy('batch')->get();

        $this->assertCount(2, $batches);
        $this->assertSame('40.000', $batches[0]->stock_opname);
        $this->assertSame('60.000', $batches[1]->stock_opname);

        $detail->refresh();
        $this->assertSame('100.000', $detail->received_quantity);
        $this->assertSame(ProductPurchaseDetail::STATUS_RECEIVED, $detail->receipt_status);
    }

    public function test_receiving_more_than_outstanding_is_rejected(): void
    {
        $this->actingAsOwner();
        $detail = $this->pendingDetail('100.000');

        $this->receive($detail, '40');
        $this->receive($detail, '61')->assertSessionHasErrors('quantity');

        $detail->refresh();
        $this->assertSame('40.000', $detail->received_quantity);
        $this->assertSame(1, ProductStock::where('product_id', $detail->product_id)->count());
    }

    public function test_repeated_submit_after_completion_is_rejected(): void
    {
        $this->actingAsOwner();
        $detail = $this->pendingDetail('100.000');

        $this->receive($detail, '100')->assertRedirect();
        $this->receive($detail, '100')->assertSessionHasErrors('quantity');

        $detail->refresh();
        $this->assertSame('100.000', $detail->received_quantity);
        $this->assertSame(1, ProductStock::where('product_id', $detail->product_id)->count());
    }

    public function test_zero_quantity_is_rejected(): void
    {
        $this->actingAsOwner();
        $detail = $this->pendingDetail('100.000');

        $this->receive($detail, '0')->assertSessionHasErrors('quantity');

        $this->assertSame(0, ProductStock::where('product_id', $detail->product_id)->count());
    }

    public function test_earlier_shipment_is_consumed_first_by_fifo(): void
    {
        $this->actingAsOwner();
        $detail = $this->pendingDetail('100.000');

        $this->receive($detail, '40');
        $this->receive($detail, '60');

        $allocations = app(ProductStockService::class)
            ->allocateStockFifo($detail->product_id, '50.000');

        $firstBatch = ProductStock::where('product_id', $detail->product_id)
            ->orderBy('batch')->first();

        $this->assertSame($firstBatch->id, $allocations[0]['stock_id']);
        $this->assertSame('40.000', $allocations[0]['quantity']);
        $this->assertSame('10.000', $allocations[1]['quantity']);
    }

    public function test_receipt_can_override_expired_date_per_shipment(): void
    {
        $this->actingAsOwner();
        $detail = $this->pendingDetail('100.000', $this->futureDate(30));

        $this->receive($detail, '40', ['expired_date' => $this->futureDate(90)]);

        $batch = ProductStock::where('product_id', $detail->product_id)->first();
        $this->assertSame($this->futureDate(90), $batch->expired_date->toDateString());
    }

    public function test_reversal_removes_batch_and_restores_outstanding(): void
    {
        $this->actingAsOwner();
        $detail = $this->pendingDetail('100.000');
        $this->receive($detail, '40');

        $receipt = ProductPurchaseReceipt::firstOrFail();
        $batchId = $receipt->product_stock_id;

        $this->delete('/penerimaan/'.$receipt->id)->assertRedirect();

        $this->assertNull(ProductStock::find($batchId));
        $this->assertSame(0, ProductPurchaseReceipt::count());

        $detail->refresh();
        $this->assertSame('0.000', $detail->received_quantity);
        $this->assertSame(ProductPurchaseDetail::STATUS_PENDING, $detail->receipt_status);
    }

    public function test_reversal_is_rejected_when_batch_already_consumed(): void
    {
        $this->actingAsOwner();
        $detail = $this->pendingDetail('100.000');
        $this->receive($detail, '40');

        $receipt = ProductPurchaseReceipt::firstOrFail();

        // Simulasikan 15 karung sudah terjual lewat kasir.
        ProductStock::whereKey($receipt->product_stock_id)->update(['stock_opname' => '25.000']);

        $this->delete('/penerimaan/'.$receipt->id)->assertSessionHasErrors('receipt');

        $this->assertSame(1, ProductPurchaseReceipt::count());
        $detail->refresh();
        $this->assertSame('40.000', $detail->received_quantity);
    }

    public function test_close_remaining_ends_the_line_without_touching_stock(): void
    {
        $this->actingAsOwner();
        $detail = $this->pendingDetail('100.000');
        $this->receive($detail, '90');

        $this->post('/penerimaan/'.$detail->id.'/tutup', [
            'reason' => 'Supplier kehabisan stok',
        ])->assertRedirect();

        $detail->refresh();
        $this->assertSame(ProductPurchaseDetail::STATUS_CLOSED, $detail->receipt_status);
        $this->assertSame('Supplier kehabisan stok', $detail->closed_reason);
        $this->assertNotNull($detail->closed_at);

        $total = ProductStock::where('product_id', $detail->product_id)->sum('stock_opname');
        $this->assertSame('90.000', (string) $total);

        $this->assertSame(0, ProductPurchaseDetail::query()->outstanding()->count());
    }

    public function test_receiving_a_closed_line_is_rejected(): void
    {
        $this->actingAsOwner();
        $detail = $this->pendingDetail('100.000');
        $this->receive($detail, '90');
        $this->post('/penerimaan/'.$detail->id.'/tutup', ['reason' => 'Habis']);

        $this->receive($detail, '10')->assertSessionHasErrors('quantity');

        $detail->refresh();
        $this->assertSame('90.000', $detail->received_quantity);
    }

    public function test_outstanding_list_shows_pending_lines_only(): void
    {
        $this->actingAsOwner();
        $pending = $this->pendingDetail('100.000');

        $doneProduct = $this->makeProduct('P-999');
        $this->createPurchase([$this->line($doneProduct, '10.000', directStock: true)]);

        $response = $this->get('/penerimaan');

        $response->assertOk();
        $response->assertSee($pending->product_name);
        $response->assertDontSee('Produk P-999');
    }
}
