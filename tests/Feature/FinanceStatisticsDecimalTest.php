<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjamin statistik finance mempertahankan presisi 3 desimal pada nilai
 * nominal (penjualan, laba kotor, chart), sementara total transaksi tetap
 * bilangan bulat.
 *
 * CATATAN: feature test ini membutuhkan migrate database; saat ini
 * terblokir oleh migration lama yang tidak kompatibel SQLite
 * (drop foreign key by name). Dapat dijalankan setelah blocker itu
 * dibereskan.
 */
class FinanceStatisticsDecimalTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsOwner(): void
    {
        $this->actingAs(User::factory()->create());
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

    private function makeTransaction(float $total, bool $isPaid = true): Transaction
    {
        return Transaction::create([
            'total_quantity' => 1.000,
            'total_price' => $total,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => $isPaid,
            'transaction_date' => now(),
        ]);
    }

    public function test_stats_preserve_decimal_sales_and_count(): void
    {
        $this->actingAsOwner();

        $trx = $this->makeTransaction(100.125);
        $this->makeTransaction(50.000);

        $response = $this->get('/laporan-keuangan');

        $response->assertOk();

        $stats = $response->viewData('stats');

        $this->assertEquals(150.125, (float) $stats['range_sales']);
        $this->assertEquals(2, $stats['total_transactions']);
    }

    public function test_profit_loss_preserves_decimal_precision(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $trx = $this->makeTransaction(100.125);

        TransactionDetail::create([
            'transaction_id' => $trx->id,
            'product_id' => $productId,
            'product_price' => 100.125,
            'buying_price' => 50.000,
            'quantity' => 1.000,
            'total_price' => 100.125,
        ]);

        $response = $this->get('/laporan-keuangan');

        $response->assertOk();

        $profitLoss = $response->viewData('profitLoss');

        $this->assertEquals(100.125, (float) $profitLoss['revenue']);
        $this->assertEquals(50.000, (float) $profitLoss['cogs']);
        $this->assertEquals(50.125, (float) $profitLoss['gross_profit']);
    }

    public function test_chart_data_receives_decimal_values(): void
    {
        $this->actingAsOwner();

        $this->makeTransaction(100.125);

        $response = $this->get('/laporan-keuangan');

        $response->assertOk();

        $chartData = $response->viewData('chartData');
        $values = array_map('floatval', $chartData['values']);

        $this->assertContains(100.125, $values);
    }
}