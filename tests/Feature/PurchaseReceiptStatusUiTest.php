<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPurchases;
use Tests\TestCase;

/**
 * Status penerimaan yang terlihat user: badge di daftar nota, checkbox di
 * form, dan hitungan di sidebar.
 */
class PurchaseReceiptStatusUiTest extends TestCase
{
    use BuildsPurchases;
    use RefreshDatabase;

    public function test_purchase_list_flags_a_nota_that_still_waits_for_goods(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $this->createPurchase([$this->line($productId, '100.000', directStock: false)]);

        $this->get('/purchase')
            ->assertOk()
            ->assertSee('1 item belum datang');
    }

    public function test_purchase_list_marks_a_fully_received_nota_as_complete(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $this->createPurchase([$this->line($productId, '10.000', directStock: true)]);

        $this->get('/purchase')
            ->assertOk()
            ->assertSee('Lengkap')
            ->assertDontSee('item belum datang');
    }

    public function test_outstanding_lines_count_ignores_closed_lines(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([$this->line($productId, '100.000', directStock: false)]);
        $detail = $this->detailOf($purchase);

        $this->assertSame(1, $purchase->fresh()->outstanding_lines_count);

        $this->post('/penerimaan/'.$detail->id.'/terima', [
            'quantity' => '90', 'received_date' => now()->toDateString(),
        ]);
        $this->assertSame(1, $purchase->fresh()->outstanding_lines_count);

        $this->post('/penerimaan/'.$detail->id.'/tutup', ['reason' => 'Habis']);
        $this->assertSame(0, $purchase->fresh()->outstanding_lines_count);
    }

    public function test_create_form_offers_the_per_line_direct_stock_choice(): void
    {
        $this->actingAsOwner();
        $this->makeProduct();

        $response = $this->get('/purchase/create')->assertOk();

        // Pasangan hidden "0" + checkbox "1": tanpa keduanya, baris yang tidak
        // dicentang tidak akan terkirim sama sekali.
        $response->assertSee('name="products[0][direct_stock]" value="0"', false);
        $response->assertSee('name="products[0][direct_stock]" value="1"', false);
        $response->assertSee('Langsung masuk stok');

        // Default tercentang: perilaku lama tetap berlaku kalau user diam saja.
        $this->assertMatchesRegularExpression(
            '/name="products\[0\]\[direct_stock\]" value="1"[^>]*checked/',
            $response->getContent()
        );
    }

    public function test_edit_form_shows_receipt_status_instead_of_the_checkbox(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([$this->line($productId, '100.000', directStock: false)]);

        $this->get('/purchase/'.$purchase->id.'/edit')
            ->assertOk()
            ->assertSee('Barang belum datang')
            ->assertDontSee('products[0][direct_stock]', false);
    }

    public function test_sidebar_shows_the_number_of_lines_waiting_for_goods(): void
    {
        $this->actingAsOwner();
        $a = $this->makeProduct('P-001');
        $b = $this->makeProduct('P-002');

        $this->createPurchase([
            $this->line($a, '100.000', directStock: false),
            $this->line($b, '50.000', directStock: false),
        ]);

        $this->get('/purchase')
            ->assertOk()
            ->assertSee('Penerimaan Barang');

        $this->assertSame(2, app(\App\Services\GoodsReceiptService::class)->outstandingCount());
    }
}
