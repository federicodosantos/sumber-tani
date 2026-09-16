<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response->assertHeader('content-disposition', 'attachment; filename=laporan-penjualan.pdf');
    }
}
