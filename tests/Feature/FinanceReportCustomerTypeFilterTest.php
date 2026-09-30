<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class FinanceReportCustomerTypeFilterTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsOwner(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'OWNER',
        ]));
    }

    private function createCustomer(string $type, string $name): Customer
    {
        return Customer::create([
            'name' => $name,
            'type' => $type,
            'phone_number' => '081234567890',
            'address' => 'Jl. Petani No. 1',
        ]);
    }

    private function createTransactionWithDetail(float $total, float $buyingPrice = 10000): Transaction
    {
        $category = ItemCategory::firstOrCreate(['name' => 'Kategori Test']);
        $product = Product::firstOrCreate(
            ['code_id' => 'PRD-TEST-1'],
            ['name' => 'Pupuk Urea', 'item_category_id' => $category->id]
        );

        $trx = Transaction::create([
            'total_quantity' => 1.000,
            'total_price' => $total,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'transaction_date' => now(),
        ]);

        TransactionDetail::create([
            'transaction_id' => $trx->id,
            'product_id' => $product->id,
            'product_price' => $total,
            'buying_price' => $buyingPrice,
            'quantity' => 1.000,
            'total_price' => $total,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $trx;
    }

    private function attachCustomerToTransaction(Transaction $trx, Customer $customer): Invoice
    {
        return Invoice::create([
            'customer_id' => $customer->id,
            'transaction_id' => $trx->id,
            'debts' => 0,
            'type' => Invoice::TYPE_PURCHASE,
            'inv_code' => Invoice::generateInvCode(Invoice::TYPE_PURCHASE),
        ]);
    }

    public function test_default_filter_includes_all_customer_types(): void
    {
        $this->actingAsOwner();

        $custR1 = $this->createCustomer('r1', 'Toko Maju R1');
        $custR2 = $this->createCustomer('r2', 'Toko Berkah R2');

        $trxR1 = $this->createTransactionWithDetail(100000);
        $this->attachCustomerToTransaction($trxR1, $custR1);

        $trxR2 = $this->createTransactionWithDetail(200000);
        $this->attachCustomerToTransaction($trxR2, $custR2);

        $trxKonsumen = $this->createTransactionWithDetail(50000); // no invoice / guest

        $response = $this->get('/laporan-keuangan');

        $response->assertOk();
        $stats = $response->viewData('stats');
        $reports = $response->viewData('financeReports');

        $this->assertEquals(350000, (float) $stats['range_sales']);
        $this->assertEquals(3, $stats['total_transactions']);
        $this->assertCount(3, $reports);
    }

    public function test_filter_by_r1_only(): void
    {
        $this->actingAsOwner();

        $custR1 = $this->createCustomer('r1', 'Toko Maju R1');
        $custR2 = $this->createCustomer('r2', 'Toko Berkah R2');

        $trxR1 = $this->createTransactionWithDetail(100000);
        $this->attachCustomerToTransaction($trxR1, $custR1);

        $trxR2 = $this->createTransactionWithDetail(200000);
        $this->attachCustomerToTransaction($trxR2, $custR2);

        $trxKonsumen = $this->createTransactionWithDetail(50000);

        $response = $this->get('/laporan-keuangan?customer_types[]=r1');

        $response->assertOk();
        $stats = $response->viewData('stats');
        $reports = $response->viewData('financeReports');
        $profitLoss = $response->viewData('profitLoss');

        $this->assertEquals(100000, (float) $stats['range_sales']);
        $this->assertEquals(1, $stats['total_transactions']);
        $this->assertCount(1, $reports);
        $this->assertEquals($trxR1->id, $reports->first()->id);
        $this->assertEquals(100000, (float) $profitLoss['revenue']);
    }

    public function test_filter_by_r2_only(): void
    {
        $this->actingAsOwner();

        $custR1 = $this->createCustomer('r1', 'Toko Maju R1');
        $custR2 = $this->createCustomer('r2', 'Toko Berkah R2');

        $trxR1 = $this->createTransactionWithDetail(100000);
        $this->attachCustomerToTransaction($trxR1, $custR1);

        $trxR2 = $this->createTransactionWithDetail(200000);
        $this->attachCustomerToTransaction($trxR2, $custR2);

        $trxKonsumen = $this->createTransactionWithDetail(50000);

        $response = $this->get('/laporan-keuangan?customer_types[]=r2');

        $response->assertOk();
        $stats = $response->viewData('stats');
        $reports = $response->viewData('financeReports');

        $this->assertEquals(200000, (float) $stats['range_sales']);
        $this->assertEquals(1, $stats['total_transactions']);
        $this->assertCount(1, $reports);
        $this->assertEquals($trxR2->id, $reports->first()->id);
    }

    public function test_filter_by_konsumen_only(): void
    {
        $this->actingAsOwner();

        $custR1 = $this->createCustomer('r1', 'Toko Maju R1');
        $custR2 = $this->createCustomer('r2', 'Toko Berkah R2');

        $trxR1 = $this->createTransactionWithDetail(100000);
        $this->attachCustomerToTransaction($trxR1, $custR1);

        $trxR2 = $this->createTransactionWithDetail(200000);
        $this->attachCustomerToTransaction($trxR2, $custR2);

        $trxKonsumen = $this->createTransactionWithDetail(50000);

        $response = $this->get('/laporan-keuangan?customer_types[]=konsumen');

        $response->assertOk();
        $stats = $response->viewData('stats');
        $reports = $response->viewData('financeReports');

        $this->assertEquals(50000, (float) $stats['range_sales']);
        $this->assertEquals(1, $stats['total_transactions']);
        $this->assertCount(1, $reports);
        $this->assertEquals($trxKonsumen->id, $reports->first()->id);
    }

    public function test_filter_multiple_r1_and_konsumen(): void
    {
        $this->actingAsOwner();

        $custR1 = $this->createCustomer('r1', 'Toko Maju R1');
        $custR2 = $this->createCustomer('r2', 'Toko Berkah R2');

        $trxR1 = $this->createTransactionWithDetail(100000);
        $this->attachCustomerToTransaction($trxR1, $custR1);

        $trxR2 = $this->createTransactionWithDetail(200000);
        $this->attachCustomerToTransaction($trxR2, $custR2);

        $trxKonsumen = $this->createTransactionWithDetail(50000);

        $response = $this->get('/laporan-keuangan?customer_types[]=r1&customer_types[]=konsumen');

        $response->assertOk();
        $stats = $response->viewData('stats');
        $reports = $response->viewData('financeReports');

        $this->assertEquals(150000, (float) $stats['range_sales']);
        $this->assertEquals(2, $stats['total_transactions']);
        $this->assertCount(2, $reports);
        $reportIds = $reports->pluck('id')->all();
        $this->assertContains($trxR1->id, $reportIds);
        $this->assertContains($trxKonsumen->id, $reportIds);
        $this->assertNotContains($trxR2->id, $reportIds);
    }

    public function test_filter_ignores_duplicate_and_indexed_params(): void
    {
        // Regresi untuk URL campuran sisa paginasi (customer_types[0..N])
        // + append (customer_types[]): duplikat harus diabaikan, R2 tetap tersingkir.
        $this->actingAsOwner();

        $custR1 = $this->createCustomer('r1', 'Toko Maju R1');
        $custR2 = $this->createCustomer('r2', 'Toko Berkah R2');

        $trxR1 = $this->createTransactionWithDetail(100000);
        $this->attachCustomerToTransaction($trxR1, $custR1);

        $trxR2 = $this->createTransactionWithDetail(200000);
        $this->attachCustomerToTransaction($trxR2, $custR2);

        $trxKonsumen = $this->createTransactionWithDetail(50000);

        $response = $this->get('/laporan-keuangan?customer_types[0]=r1&customer_types[]=r1&customer_types[1]=konsumen&customer_types[]=konsumen');

        $response->assertOk();
        $stats = $response->viewData('stats');
        $reports = $response->viewData('financeReports');

        $this->assertEquals(150000, (float) $stats['range_sales']);
        $this->assertEquals(2, $stats['total_transactions']);
        $this->assertCount(2, $reports);
        $reportIds = $reports->pluck('id')->all();
        $this->assertContains($trxR1->id, $reportIds);
        $this->assertContains($trxKonsumen->id, $reportIds);
        $this->assertNotContains($trxR2->id, $reportIds);
    }

    public function test_filter_multiple_comma_separated_string(): void
    {
        $this->actingAsOwner();

        $custR1 = $this->createCustomer('r1', 'Toko Maju R1');
        $custR2 = $this->createCustomer('r2', 'Toko Berkah R2');

        $trxR1 = $this->createTransactionWithDetail(100000);
        $this->attachCustomerToTransaction($trxR1, $custR1);

        $trxR2 = $this->createTransactionWithDetail(200000);
        $this->attachCustomerToTransaction($trxR2, $custR2);

        $response = $this->get('/laporan-keuangan?customer_types=r1,r2');

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertEquals(300000, (float) $stats['range_sales']);
        $this->assertEquals(2, $stats['total_transactions']);
    }

    public function test_download_pdf_applies_customer_type_filter(): void
    {
        $this->actingAsOwner();

        $custR1 = $this->createCustomer('r1', 'Toko Maju R1');
        $custR2 = $this->createCustomer('r2', 'Toko Berkah R2');

        $trxR1 = $this->createTransactionWithDetail(100000);
        $this->attachCustomerToTransaction($trxR1, $custR1);

        $trxR2 = $this->createTransactionWithDetail(200000);
        $this->attachCustomerToTransaction($trxR2, $custR2);

        $category = ItemCategory::first();

        $response = $this->post('/laporan-keuangan/download', [
            'range_type' => '7days',
            'format_time' => 'harian',
            'download_by' => 'category',
            'category_ids' => [$category->id],
            'customer_types' => ['r1'],
        ]);

        $response->assertOk();
        // Nama file memuat cakupan: laporan-penjualan-category-<mulai>-sampai-<akhir>.pdf
        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringStartsWith('attachment;', $disposition);
        $this->assertMatchesRegularExpression(
            '/filename=laporan-penjualan-category-\d{4}-\d{2}-\d{2}-sampai-\d{4}-\d{2}-\d{2}\.pdf/',
            $disposition
        );
    }

    public function test_download_custom_range_includes_end_date_transactions(): void
    {
        // Regresi: end_date custom dulu di-parse ke 00:00:00 sehingga seluruh
        // transaksi di tanggal akhir (00:00:01–23:59:59) hilang dari PDF.
        $this->actingAsOwner();

        $category = ItemCategory::firstOrCreate(['name' => 'Kategori Test']);
        $product = Product::firstOrCreate(
            ['code_id' => 'PRD-TEST-ENDDATE'],
            ['name' => 'Produk Enddate', 'item_category_id' => $category->id]
        );

        $trxDate = Carbon::parse('2026-08-31 23:59:00');
        $trx = Transaction::create([
            'total_quantity' => 1.000,
            'total_price' => 75000,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'transaction_date' => $trxDate,
        ]);

        TransactionDetail::create([
            'transaction_id' => $trx->id,
            'product_id' => $product->id,
            'product_price' => 75000,
            'buying_price' => 50000,
            'quantity' => 1.000,
            'total_price' => 75000,
            'created_at' => $trxDate,
            'updated_at' => $trxDate,
        ]);

        Pdf::shouldReceive('loadView')->once()->with('finance.report', Mockery::on(
            fn ($data) => abs((float) $data['grandTotalSales'] - 75000) < 0.001
        ))->andReturnSelf();
        Pdf::shouldReceive('setPaper')->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('pdf-bytes'));

        $response = $this->post('/laporan-keuangan/download', [
            'range_type' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'format_time' => 'harian',
            'download_by' => 'product',
            'product_ids' => [$product->id],
        ]);

        $response->assertOk();
    }

    public function test_download_product_includes_trashed_product(): void
    {
        // Produk terhapus (soft-delete) yang dicentang harus tetap masuk PDF.
        $this->actingAsOwner();

        $category = ItemCategory::firstOrCreate(['name' => 'Kategori Test']);
        $product = Product::firstOrCreate(
            ['code_id' => 'PRD-TEST-TRASHED'],
            ['name' => 'Produk Terhapus', 'item_category_id' => $category->id]
        );

        $trxDate = Carbon::parse('2026-08-15 10:00:00');
        $trx = Transaction::create([
            'total_quantity' => 2.000,
            'total_price' => 50000,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'transaction_date' => $trxDate,
        ]);

        TransactionDetail::create([
            'transaction_id' => $trx->id,
            'product_id' => $product->id,
            'product_price' => 25000,
            'buying_price' => 15000,
            'quantity' => 2.000,
            'total_price' => 50000,
            'created_at' => $trxDate,
            'updated_at' => $trxDate,
        ]);

        // Produk dihapus SETELAH terjual — riwayatnya harus tetap terlapor.
        $product->delete();

        // Daftar modal harus memuatnya berlabel (dihapus).
        $response = $this->get('/laporan-keuangan');
        $response->assertOk();
        $products = $response->viewData('products');
        $listed = $products->firstWhere('id', $product->id);
        $this->assertNotNull($listed);
        $this->assertStringEndsWith('(dihapus)', $listed['name']);

        Pdf::shouldReceive('loadView')->once()->with('finance.report', Mockery::on(
            function ($data) {
                $totalOk = abs((float) $data['grandTotalSales'] - 50000) < 0.001;
                $labelOk = collect($data['columns'])->contains(
                    fn ($name) => str_ends_with($name, 'Produk Terhapus (dihapus)')
                );

                return $totalOk && $labelOk;
            }
        ))->andReturnSelf();
        Pdf::shouldReceive('setPaper')->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('pdf-bytes'));

        $response = $this->post('/laporan-keuangan/download', [
            'range_type' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'format_time' => 'harian',
            'download_by' => 'product',
            'product_ids' => [$product->id],
        ]);

        $response->assertOk();
    }

    public function test_download_category_includes_trashed_category(): void
    {
        // Kategori terhapus (soft-delete) yang dicentang harus tetap masuk PDF.
        $this->actingAsOwner();

        $category = ItemCategory::create(['name' => 'Kategori Terhapus']);
        $product = Product::firstOrCreate(
            ['code_id' => 'PRD-TEST-TRASHED-CAT'],
            ['name' => 'Produk Kategori Terhapus', 'item_category_id' => $category->id]
        );

        $trxDate = Carbon::parse('2026-08-15 10:00:00');
        $trx = Transaction::create([
            'total_quantity' => 1.000,
            'total_price' => 30000,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'transaction_date' => $trxDate,
        ]);

        TransactionDetail::create([
            'transaction_id' => $trx->id,
            'product_id' => $product->id,
            'product_price' => 30000,
            'buying_price' => 20000,
            'quantity' => 1.000,
            'total_price' => 30000,
            'created_at' => $trxDate,
            'updated_at' => $trxDate,
        ]);

        $category->delete();

        $response = $this->get('/laporan-keuangan');
        $response->assertOk();
        $categories = $response->viewData('categories');
        $listed = $categories->firstWhere('id', $category->id);
        $this->assertNotNull($listed);
        $this->assertStringEndsWith('(dihapus)', $listed['name']);

        Pdf::shouldReceive('loadView')->once()->with('finance.report', Mockery::on(
            function ($data) {
                $totalOk = abs((float) $data['grandTotalSales'] - 30000) < 0.001;
                $labelOk = collect($data['columns'])->contains(
                    fn ($name) => str_ends_with($name, 'Kategori Terhapus (dihapus)')
                );

                return $totalOk && $labelOk;
            }
        ))->andReturnSelf();
        Pdf::shouldReceive('setPaper')->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('pdf-bytes'));

        $response = $this->post('/laporan-keuangan/download', [
            'range_type' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'format_time' => 'harian',
            'download_by' => 'category',
            'category_ids' => [$category->id],
        ]);

        $response->assertOk();
    }

    public function test_download_recap_reconciles_to_header_totals(): void
    {
        // Rekonsiliasi: kotor − diskon + penyesuaian = Σ header.
        // Nota diskon: rincian 100rb, diskon 20rb, header 80rb.
        // Nota noise warisan: rincian 40rb, diskon 0, header 50rb.
        // Ekspektasi: kotor 140rb, diskon 20rb, penyesuaian 10rb, bersih 130rb.
        $this->actingAsOwner();

        $category = ItemCategory::firstOrCreate(['name' => 'Kategori Test']);
        $productA = Product::firstOrCreate(
            ['code_id' => 'PRD-TEST-RECAP-A'],
            ['name' => 'Produk Rekap A', 'item_category_id' => $category->id]
        );
        $productB = Product::firstOrCreate(
            ['code_id' => 'PRD-TEST-RECAP-B'],
            ['name' => 'Produk Rekap B', 'item_category_id' => $category->id]
        );

        $trxDate = Carbon::parse('2026-08-15 10:00:00');

        $trxDisc = Transaction::create([
            'total_quantity' => 1.000,
            'total_price' => 80000,
            'discount' => 20000,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'transaction_date' => $trxDate,
        ]);
        TransactionDetail::create([
            'transaction_id' => $trxDisc->id,
            'product_id' => $productA->id,
            'product_price' => 100000,
            'buying_price' => 60000,
            'quantity' => 1.000,
            'total_price' => 100000,
            'created_at' => $trxDate,
            'updated_at' => $trxDate,
        ]);

        $trxNoise = Transaction::create([
            'total_quantity' => 1.000,
            'total_price' => 50000,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'transaction_date' => $trxDate,
        ]);
        TransactionDetail::create([
            'transaction_id' => $trxNoise->id,
            'product_id' => $productB->id,
            'product_price' => 40000,
            'buying_price' => 25000,
            'quantity' => 1.000,
            'total_price' => 40000,
            'created_at' => $trxDate,
            'updated_at' => $trxDate,
        ]);

        Pdf::shouldReceive('loadView')->once()->with('finance.report', Mockery::on(
            function ($data) {
                $grossOk = abs((float) $data['grandTotalSales'] - 140000) < 0.001;
                $discOk = abs((float) $data['discountTotal'] - 20000) < 0.001;
                $adjOk = abs((float) $data['adjustmentTotal'] - 10000) < 0.001;
                $netOk = abs((float) $data['netRevenue'] - 130000) < 0.001;

                return $grossOk && $discOk && $adjOk && $netOk;
            }
        ))->andReturnSelf();
        Pdf::shouldReceive('setPaper')->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('pdf-bytes'));

        $response = $this->post('/laporan-keuangan/download', [
            'range_type' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'format_time' => 'harian',
            'download_by' => 'product',
            'product_ids' => [$productA->id, $productB->id],
        ]);

        $response->assertOk();
    }

    private function renderReport(array $recap): string
    {
        return view('finance.report', array_merge([
            'data' => collect(),
            'pivot' => ['15 Agustus 2026' => [1 => 50000]],
            'columns' => [1 => 'Produk A'],
            'totalSales' => [1 => 50000],
            'grandTotalQty' => 2,
            'grandTotalSales' => 50000,
            'totalQty' => [1 => 2],
            'isLandscape' => true,
            'downloadBy' => 'product',
            'startDate' => '15 Agustus 2026',
            'endDate' => '15 Agustus 2026',
            'customerTypes' => ['r1', 'r2', 'konsumen'],
            'customerTypesLabel' => 'Semua (R1, R2, Konsumen)',
        ], $recap))->render();
    }

    public function test_report_hides_zero_recap_rows(): void
    {
        // Tanpa diskon & noise (mis. September): baris rekap nol disembunyikan.
        $html = $this->renderReport([
            'discountTotal' => '0.000',
            'adjustmentTotal' => '0.000',
            'netRevenue' => '50000.000',
        ]);

        $this->assertStringNotContainsString('TOTAL DISKON', $html);
        $this->assertStringNotContainsString('PENYESUAIAN NOTA', $html);
        $this->assertStringContainsString('PENDAPATAN BERSIH', $html);
    }

    public function test_report_shows_nonzero_recap_rows(): void
    {
        // Ada diskon & noise (mis. Januari): ketiga baris tampil.
        $html = $this->renderReport([
            'discountTotal' => '20000.000',
            'adjustmentTotal' => '10000.000',
            'netRevenue' => '40000.000',
        ]);

        $this->assertStringContainsString('TOTAL DISKON', $html);
        $this->assertStringContainsString('PENYESUAIAN NOTA', $html);
        $this->assertStringContainsString('PENDAPATAN BERSIH', $html);
        $this->assertStringContainsString('Rp 20.000', $html);
    }

    /**
     * Buat 11 produk @ Rp1.000 untuk memaksa mode portrait (>10 kolom).
     *
     * @return array<int> product ids
     */
    private function makeElevenProducts(ItemCategory $category): array
    {
        $ids = [];
        for ($i = 1; $i <= 11; $i++) {
            $product = Product::firstOrCreate(
                ['code_id' => 'PRD-TEST-POR-'.$i],
                ['name' => 'Produk Portrait '.$i, 'item_category_id' => $category->id]
            );
            $ids[] = $product->id;
        }

        return $ids;
    }

    private function makeDetail(int $trxId, int $productId, float $price, float $qty, Carbon $date): void
    {
        TransactionDetail::create([
            'transaction_id' => $trxId,
            'product_id' => $productId,
            'product_price' => $price,
            'buying_price' => $price / 2,
            'quantity' => $qty,
            'total_price' => $price * $qty,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    public function test_download_portrait_zero_adjustment_is_exact(): void
    {
        // Regresi gejala 1+2: di portrait $grandTotalSales = 0 dan debu float
        // bisa memunculkan baris penyesuaian. Harus '0.000' persis.
        $this->actingAsOwner();

        $category = ItemCategory::firstOrCreate(['name' => 'Kategori Test']);
        $ids = $this->makeElevenProducts($category);
        $trxDate = Carbon::parse('2026-09-15 10:00:00');

        $trx = Transaction::create([
            'total_quantity' => 11.000,
            'total_price' => 11000,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'transaction_date' => $trxDate,
        ]);
        foreach ($ids as $pid) {
            $this->makeDetail($trx->id, $pid, 1000, 1, $trxDate);
        }

        Pdf::shouldReceive('loadView')->once()->with('finance.report', Mockery::on(
            function ($data) {
                return $data['isLandscape'] === false
                    && $data['adjustmentTotal'] === '0.000'
                    && $data['discountTotal'] === '0.000'
                    && abs((float) $data['netRevenue'] - 11000) < 0.001;
            }
        ))->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('pdf-bytes'));

        $response = $this->post('/laporan-keuangan/download', [
            'range_type' => 'custom',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'format_time' => 'harian',
            'download_by' => 'product',
            'product_ids' => $ids,
        ]);

        $response->assertOk();
    }

    public function test_download_portrait_recap_matches_headers(): void
    {
        // Portrait + diskon + noise: kotor 149rb, diskon 20rb,
        // penyesuaian 10rb, bersih 139rb = Σ header.
        $this->actingAsOwner();

        $category = ItemCategory::firstOrCreate(['name' => 'Kategori Test']);
        $ids = $this->makeElevenProducts($category);
        $trxDate = Carbon::parse('2026-09-15 10:00:00');

        // Nota diskon: produk 1 (100rb) + produk 3-6 (4×1rb) = 104rb − 20rb = 84rb.
        $trxDisc = Transaction::create([
            'total_quantity' => 5.000,
            'total_price' => 84000,
            'discount' => 20000,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'transaction_date' => $trxDate,
        ]);
        $this->makeDetail($trxDisc->id, $ids[0], 100000, 1, $trxDate);
        foreach (array_slice($ids, 2, 4) as $pid) {
            $this->makeDetail($trxDisc->id, $pid, 1000, 1, $trxDate);
        }

        // Nota noise: produk 2 (40rb) + produk 7-11 (5×1rb) = 45rb, header 55rb.
        $trxNoise = Transaction::create([
            'total_quantity' => 6.000,
            'total_price' => 55000,
            'discount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => true,
            'transaction_date' => $trxDate,
        ]);
        $this->makeDetail($trxNoise->id, $ids[1], 40000, 1, $trxDate);
        foreach (array_slice($ids, 6, 5) as $pid) {
            $this->makeDetail($trxNoise->id, $pid, 1000, 1, $trxDate);
        }

        Pdf::shouldReceive('loadView')->once()->with('finance.report', Mockery::on(
            function ($data) {
                // Portrait: $grandTotalSales memang 0 (total dihitung di Blade
                // via $totalSalesSum); rekap memakai $grossSales presisi-string.
                return $data['isLandscape'] === false
                    && (float) $data['grandTotalSales'] == 0
                    && abs((float) $data['discountTotal'] - 20000) < 0.001
                    && abs((float) $data['adjustmentTotal'] - 10000) < 0.001
                    && abs((float) $data['netRevenue'] - 139000) < 0.001;
            }
        ))->andReturnSelf();
        // Portrait: setPaper tidak dipanggil.
        Pdf::shouldReceive('download')->once()->andReturn(response('pdf-bytes'));

        $response = $this->post('/laporan-keuangan/download', [
            'range_type' => 'custom',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'format_time' => 'harian',
            'download_by' => 'product',
            'product_ids' => $ids,
        ]);

        $response->assertOk();
    }
}
