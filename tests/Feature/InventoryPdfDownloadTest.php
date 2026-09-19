<?php

namespace Tests\Feature;

use App\Models\ProductStock;
use App\Services\GoodsReceiptService;
use App\Services\ProductStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPurchases;
use Tests\TestCase;

class InventoryPdfDownloadTest extends TestCase
{
    use BuildsPurchases;
    use RefreshDatabase;

    public function test_guest_cannot_download_inventory_pdf(): void
    {
        $this->get('/laporan-keuangan/download-persediaan')->assertRedirect('/login');
    }

    public function test_owner_can_download_current_inventory_pdf(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $this->createPurchase([
            $this->line($productId, '10.000', directStock: true, hetPrice: '50000.000'),
        ]);

        $filename = 'laporan-persediaan-'.now()->format('Y-m-d').'.pdf';

        $response = $this->get('/laporan-keuangan/download-persediaan');

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename='.$filename);
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_on_hand_report_uses_stock_times_hpp_and_skips_empty_batches(): void
    {
        $this->actingAsOwner();
        $onHandId = $this->makeProduct('P-ONHAND');
        $emptyId = $this->makeProduct('P-EMPTY');

        $this->createPurchase([
            $this->line($onHandId, '10.000', directStock: true, hetPrice: '50000.000'),
        ]);
        $this->createPurchase([
            $this->line($emptyId, '5.000', directStock: true, hetPrice: '20000.000'),
        ]);

        ProductStock::query()->where('product_id', $emptyId)->update(['stock_opname' => 0]);

        $report = app(ProductStockService::class)->getOnHandInventoryReport();

        $this->assertCount(1, $report['rows']);
        $this->assertSame('P-ONHAND', $report['rows'][0]['code']);
        $this->assertSame('10.000', $report['rows'][0]['quantity']);
        $this->assertSame('50000.000', $report['rows'][0]['unit_price']);
        $this->assertSame('500000.000', $report['rows'][0]['value']);
        $this->assertSame('500000.000', $report['total_value']);
        $this->assertSame(0, $report['incomplete_count']);
    }

    public function test_goods_in_transit_lines_match_balance_sheet_value(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct('P-TRANSIT');

        $this->createPurchase([
            $this->line($productId, '100.000', directStock: false, hetPrice: '50000.000'),
        ]);

        $inTransit = app(GoodsReceiptService::class)->goodsInTransitLines();

        $this->assertCount(1, $inTransit['rows']);
        $this->assertSame('100.000', $inTransit['rows'][0]['quantity']);
        $this->assertSame('50000.000', $inTransit['rows'][0]['net_price']);
        $this->assertSame('5000000.000', $inTransit['rows'][0]['value']);
        $this->assertSame('5000000.000', $inTransit['total_value']);
    }

    public function test_incomplete_hpp_is_flagged_on_on_hand_report(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct('P-NO-HPP');

        $this->createPurchase([
            $this->line($productId, '8.000', directStock: true, hetPrice: '15000.000'),
        ]);

        ProductStock::query()->where('product_id', $productId)->update(['unit_price' => 0]);

        $report = app(ProductStockService::class)->getOnHandInventoryReport();

        $this->assertCount(1, $report['rows']);
        $this->assertTrue($report['rows'][0]['missing_hpp']);
        $this->assertSame(1, $report['incomplete_count']);
        $this->assertSame('0.000', $report['total_value']);
    }

    public function test_finance_index_shows_inventory_pdf_button(): void
    {
        $this->actingAsOwner();

        $this->get('/laporan-keuangan')
            ->assertOk()
            ->assertSee('Download PDF Persediaan')
            ->assertSee(route('finance.download.inventory', absolute: false));
    }
}
