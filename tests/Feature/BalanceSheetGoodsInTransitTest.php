<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPurchases;
use Tests\TestCase;

/**
 * Barang sudah dibayar tapi belum datang tetap harus muncul sebagai aset.
 *
 * Tanpa pos ini, pembelian tunai yang barangnya dititip akan memotong kas
 * tanpa menambah aset apa pun — ekuitas turun padahal tidak ada kerugian.
 */
class BalanceSheetGoodsInTransitTest extends TestCase
{
    use BuildsPurchases;
    use RefreshDatabase;

    private function balanceSheet(): array
    {
        $response = $this->get('/laporan-keuangan');
        $response->assertOk();

        return $response->viewData('balanceSheet');
    }

    public function test_pending_line_is_reported_as_goods_in_transit(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        // 100 karung @ Rp 50.000, belum datang sama sekali.
        $this->createPurchase([
            $this->line($productId, '100.000', directStock: false, hetPrice: '50000.000'),
        ]);

        $sheet = $this->balanceSheet();

        $this->assertSame('5000000.000', $sheet['assets']['goods_in_transit']);
        $this->assertSame('0.000', $sheet['assets']['inventory']);
    }

    public function test_partially_received_line_splits_between_inventory_and_transit(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([
            $this->line($productId, '100.000', directStock: false, hetPrice: '50000.000'),
        ]);
        $detail = $this->detailOf($purchase);

        $this->post('/penerimaan/'.$detail->id.'/terima', [
            'quantity' => '40', 'received_date' => now()->toDateString(),
        ]);

        $sheet = $this->balanceSheet();

        $this->assertSame('2000000.000', $sheet['assets']['inventory']);
        $this->assertSame('3000000.000', $sheet['assets']['goods_in_transit']);
    }

    public function test_direct_line_never_counts_as_goods_in_transit(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $this->createPurchase([
            $this->line($productId, '10.000', directStock: true, hetPrice: '50000.000'),
        ]);

        $sheet = $this->balanceSheet();

        $this->assertSame('0.000', $sheet['assets']['goods_in_transit']);
        $this->assertSame('500000.000', $sheet['assets']['inventory']);
    }

    public function test_closed_remainder_leaves_goods_in_transit(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([
            $this->line($productId, '100.000', directStock: false, hetPrice: '50000.000'),
        ]);
        $detail = $this->detailOf($purchase);

        $this->post('/penerimaan/'.$detail->id.'/terima', [
            'quantity' => '90', 'received_date' => now()->toDateString(),
        ]);
        $this->post('/penerimaan/'.$detail->id.'/tutup', ['reason' => 'Supplier kehabisan']);

        $sheet = $this->balanceSheet();

        $this->assertSame('0.000', $sheet['assets']['goods_in_transit']);
        $this->assertSame('4500000.000', $sheet['assets']['inventory']);
    }

    public function test_total_assets_include_goods_in_transit(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $this->createPurchase([
            $this->line($productId, '100.000', directStock: false, hetPrice: '50000.000'),
        ]);

        $sheet = $this->balanceSheet();

        $expected = collect([
            $sheet['assets']['cash'],
            $sheet['assets']['inventory'],
            $sheet['assets']['goods_in_transit'],
            $sheet['assets']['receivables'],
        ])->reduce(fn ($carry, $v) => bcadd($carry, $v, 3), '0.000');

        $this->assertSame($expected, $sheet['assets']['total']);
    }
}
