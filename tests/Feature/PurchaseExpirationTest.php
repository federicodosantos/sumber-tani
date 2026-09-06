<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\ProductStock;
use App\Models\User;
use App\Services\ProductStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Fitur Product Expiration Date (Behavior Spec Rev. 3):
 * - expired_date pada purchase detail (nullable);
 * - propagasi sekali ke stock batch saat purchase dibuat;
 * - setelah dibuat, nilai purchase & stock independen;
 * - validasi after_or_equal:today hanya untuk nilai baru/berubah;
 * - hidden detail id validation-only (scoped ke purchase saat ini, tanpa duplikat).
 */
class PurchaseExpirationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsOwner(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'OWNER']));
    }

    private function makeProduct(string $code = 'P-001'): int
    {
        $category = ItemCategory::create(['name' => 'Kategori Test']);

        $product = Product::create([
            'code_id' => $code,
            'name' => 'Produk '.$code,
            'item_category_id' => $category->id,
        ]);

        return $product->id;
    }

    private function currentDate(): string
    {
        return now()->toDateString();
    }

    private function futureDate(int $days = 30): string
    {
        return now()->addDays($days)->toDateString();
    }

    private function pastDate(int $days = 30): string
    {
        return now()->subDays($days)->toDateString();
    }

    private function purchaseDate(): string
    {
        return now()->subDays(5)->toDateString();
    }

    private function line(int $productId, string $qty = '10.000', $expiredDate = null, $id = null): array
    {
        return [
            'id' => $id,
            'product_id' => $productId,
            'het_price' => '10000.000',
            'basic_discount' => '0.000',
            'additional_discount' => '0.000',
            'quantity' => $qty,
            'unit' => 'PCS',
            'expired_date' => $expiredDate,
        ];
    }

    private function payload(array $lines): array
    {
        return [
            'purchase_date' => $this->purchaseDate(),
            'ppn' => 0,
            'ppn_type' => 'percent',
            'discount_type' => 'percent',
            'discount' => 0,
            'method' => 0,
            'manual_grand_total' => null,
            'products' => $lines,
        ];
    }

    private function createPurchase(array $lines): ProductPurchase
    {
        $this->post('/purchase', $this->payload($lines))
            ->assertSessionHasNoErrors();

        return ProductPurchase::latest('id')->first();
    }

    public function test_store_persists_expired_date_on_detail(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([$this->line($productId, '10.000', $this->futureDate(30))]);

        $detail = $purchase->details()->first();
        $this->assertNotNull($detail);
        $this->assertSame($this->futureDate(30), $detail->expired_date?->toDateString());
    }

    public function test_store_propagates_expired_date_to_new_batch(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $this->createPurchase([$this->line($productId, '10.000', $this->futureDate(30))]);

        $batch = ProductStock::where('product_id', $productId)->first();
        $this->assertNotNull($batch);
        $this->assertSame($this->futureDate(30), $batch->expired_date?->toDateString());
    }

    public function test_store_null_expiry_propagates_as_null(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $this->createPurchase([$this->line($productId, '10.000', null)]);

        $batch = ProductStock::where('product_id', $productId)->first();
        $this->assertNotNull($batch);
        $this->assertNull($batch->expired_date);
    }

    public function test_store_rejects_past_expiry(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $this->post('/purchase', $this->payload([$this->line($productId, '10.000', $this->pastDate(30))]))
            ->assertSessionHasErrors(['products.0.expired_date']);
    }

    public function test_store_accepts_today_expiry(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $this->post('/purchase', $this->payload([$this->line($productId, '10.000', $this->currentDate())]))
            ->assertSessionHasNoErrors();

        $batch = ProductStock::where('product_id', $productId)->first();
        $this->assertSame($this->currentDate(), $batch->expired_date?->toDateString());
    }

    public function test_store_multiple_details_propagate_independently(): void
    {
        $this->actingAsOwner();
        $productA = $this->makeProduct('P-001');
        $productB = $this->makeProduct('P-002');

        $this->createPurchase([
            $this->line($productA, '10.000', $this->futureDate(30)),
            $this->line($productB, '5.000', null),
        ]);

        $batchA = ProductStock::where('product_id', $productA)->first();
        $batchB = ProductStock::where('product_id', $productB)->first();
        $this->assertSame($this->futureDate(30), $batchA->expired_date?->toDateString());
        $this->assertNull($batchB->expired_date);
    }

    public static function editExpiryCases(): array
    {
        return [
            'existing NULL -> valid date' => ['null_to_valid'],
            'existing valid -> NULL' => ['valid_to_null'],
            'existing expired -> unchanged' => ['expired_to_unchanged'],
            'existing expired -> valid date' => ['expired_to_valid'],
            'existing expired -> another expired date' => ['expired_to_other_expired'],
            'existing valid -> same date' => ['valid_to_same'],
            'existing valid -> today' => ['valid_to_today'],
        ];
    }

    #[DataProvider('editExpiryCases')]
    public function test_edit_conditional_expiry_validation(string $case): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        // Tanggal dihitung di dalam test (bukan di data provider statis) agar
        // timezone aplikasi (Asia/Jakarta) sudah aktif; provider statis
        // dievaluasi sebelum app bootstrap sehingga bisa bergeser 1 hari.
        $today = $this->currentDate();
        $future = $this->futureDate(30);
        $pastA = $this->pastDate(30);
        $pastB = $this->pastDate(60);

        [$initial, $submitted, $expectSuccess] = match ($case) {
            'null_to_valid' => [null, $future, true],
            'valid_to_null' => [$future, null, true],
            'expired_to_unchanged' => [$pastA, $pastA, true],
            'expired_to_valid' => [$pastA, $future, true],
            'expired_to_other_expired' => [$pastA, $pastB, false],
            'valid_to_same' => [$future, $future, true],
            'valid_to_today' => [$future, $today, true],
        };

        $createExpiry = ($initial !== null && $initial >= now()->toDateString()) ? $initial : $this->futureDate(30);

        $purchase = $this->createPurchase([$this->line($productId, '10.000', $createExpiry)]);
        $detail = $purchase->details()->first();

        // Simulasikan record historis yang expiry-nya sudah lewat (store() tidak
        // mengizinkan input tanggal lampau, tapi nilai tersimpan bisa menjadi lampau).
        if ($initial !== null && $initial < now()->toDateString()) {
            $detail->update(['expired_date' => $initial]);
        }
        $detailId = $detail->id;

        $line = $this->line($productId, '10.000', $submitted, $detailId);
        $response = $this->put("/purchase/{$purchase->id}", $this->payload([$line]));

        if ($expectSuccess) {
            $response->assertSessionHasNoErrors();
            $this->assertSame(
                $submitted !== null && $submitted !== '' ? $submitted : null,
                $purchase->fresh()->details()->first()->expired_date?->toDateString()
            );
        } else {
            $response->assertSessionHasErrors(['products.0.expired_date']);
        }
    }

    public function test_edit_new_row_with_expired_date_is_rejected(): void
    {
        $this->actingAsOwner();
        $productA = $this->makeProduct('P-001');
        $productB = $this->makeProduct('P-002');

        $purchase = $this->createPurchase([$this->line($productA, '10.000', null)]);
        $detailId = $purchase->details()->first()->id;

        $response = $this->put("/purchase/{$purchase->id}", $this->payload([
            $this->line($productA, '10.000', null, $detailId),
            $this->line($productB, '5.000', $this->pastDate(30)),
        ]));

        $response->assertSessionHasErrors(['products.1.expired_date']);
    }

    public function test_edit_new_row_with_valid_date_is_allowed(): void
    {
        $this->actingAsOwner();
        $productA = $this->makeProduct('P-001');
        $productB = $this->makeProduct('P-002');

        $purchase = $this->createPurchase([$this->line($productA, '10.000', null)]);
        $detailId = $purchase->details()->first()->id;

        $response = $this->put("/purchase/{$purchase->id}", $this->payload([
            $this->line($productA, '10.000', null, $detailId),
            $this->line($productB, '5.000', $this->futureDate(30)),
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame(
            $this->futureDate(30),
            $purchase->fresh()->details()->where('product_id', $productB)->first()->expired_date?->toDateString()
        );
    }

    public function test_edit_rejects_hidden_id_from_another_purchase(): void
    {
        $this->actingAsOwner();
        $productA = $this->makeProduct('P-001');
        $productB = $this->makeProduct('P-002');

        $purchaseA = $this->createPurchase([$this->line($productA, '10.000', null)]);
        $purchaseB = $this->createPurchase([$this->line($productB, '5.000', null)]);
        $foreignDetailId = $purchaseB->details()->first()->id;

        $response = $this->put("/purchase/{$purchaseA->id}", $this->payload([
            $this->line($productA, '10.000', null, $foreignDetailId),
        ]));

        $response->assertSessionHasErrors(['products.0.id']);
    }

    public function test_edit_rejects_nonexistent_hidden_id(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([$this->line($productId, '10.000', null)]);

        $response = $this->put("/purchase/{$purchase->id}", $this->payload([
            $this->line($productId, '10.000', null, 999999),
        ]));

        $response->assertSessionHasErrors(['products.0.id']);
    }

    public function test_edit_rejects_duplicate_hidden_ids(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        // Baris dengan expiry historis (lampau). Tanpa proteksi duplikat, dua baris
        // dengan id yang sama akan dianggap "existing unchanged" dan lolos; dengan
        // proteksi, baris kedua harus ditolak.
        $purchase = $this->createPurchase([$this->line($productId, '10.000', $this->futureDate(30))]);
        $detail = $purchase->details()->first();
        $historical = $this->pastDate(30);
        $detail->update(['expired_date' => $historical]);
        $detailId = $detail->id;

        $response = $this->put("/purchase/{$purchase->id}", $this->payload([
            $this->line($productId, '10.000', $historical, $detailId),
            $this->line($productId, '5.000', $historical, $detailId),
        ]));

        $response->assertSessionHasErrors(['products.1.id']);

        // Validasi gagal sebelum delete-and-recreate: detail tidak bermutasi.
        $this->assertSame(1, $purchase->fresh()->details()->count());
    }

    public function test_edit_render_after_validation_failure_preserves_id_expiry_pairing(): void
    {
        $this->actingAsOwner();
        $p1 = $this->makeProduct('P-001');
        $p2 = $this->makeProduct('P-002');
        $p3 = $this->makeProduct('P-003');

        $expiryA = $this->futureDate(30);
        $expiryC = $this->futureDate(180);

        $purchase = $this->createPurchase([
            $this->line($p1, '10.000', $expiryA),
            $this->line($p2, '10.000', $this->futureDate(90)),
            $this->line($p3, '10.000', $expiryC),
        ]);
        $details = $purchase->details()->get();
        $d0 = $details[0]->id;
        $d2 = $details[2]->id;

        // Baris tengah (index 1) dihapus dari submit; index submit jadi sparse [0, 2].
        // Ubah expiry baris index 2 menjadi lampau agar validasi gagal.
        $invalidExpiryC = $this->pastDate(30);
        $response = $this->put("/purchase/{$purchase->id}", $this->payload([
            0 => $this->line($p1, '10.000', $expiryA, $d0),
            2 => $this->line($p3, '10.000', $invalidExpiryC, $d2),
        ]));

        $response->assertSessionHasErrors(['products.2.expired_date']);

        // Re-render form edit: pasangan id <-> expiry dari submit harus tetap utuh,
        // bukan ditata ulang mengikuti posisi detail DB.
        $editResponse = $this->get("/purchase/{$purchase->id}/edit");
        $editResponse->assertOk();

        $html = preg_replace('/\s+/', ' ', $editResponse->getContent());
        $this->assertStringContainsString('name="products[0][id]" value="'.$d0.'"', $html);
        $this->assertStringContainsString('name="products[0][expired_date]" value="'.$expiryA.'"', $html);
        $this->assertStringContainsString('name="products[2][id]" value="'.$d2.'"', $html);
        $this->assertStringContainsString('name="products[2][expired_date]" value="'.$invalidExpiryC.'"', $html);
        $this->assertStringNotContainsString('name="products[1][id]"', $html);
    }

    public function test_edit_purchase_expiry_does_not_modify_stock_batch(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([$this->line($productId, '10.000', $this->futureDate(30))]);
        $detailId = $purchase->details()->first()->id;

        $batch = ProductStock::where('product_id', $productId)->first();
        $before = [
            'id' => $batch->id,
            'stock_opname' => (string) $batch->stock_opname,
            'unit_price' => (string) $batch->unit_price,
            'price_consument' => (string) $batch->price_consument,
            'expired_date' => $batch->expired_date?->toDateString(),
        ];
        $stockCountBefore = ProductStock::count();

        $this->put("/purchase/{$purchase->id}", $this->payload([
            $this->line($productId, '10.000', $this->futureDate(120), $detailId),
        ]))->assertSessionHasNoErrors();

        $after = ProductStock::where('product_id', $productId)->first();
        $this->assertSame($before['id'], $after->id);
        $this->assertSame($before['stock_opname'], (string) $after->stock_opname);
        $this->assertSame($before['unit_price'], (string) $after->unit_price);
        $this->assertSame($before['price_consument'], (string) $after->price_consument);
        $this->assertSame($before['expired_date'], $after->expired_date?->toDateString());
        $this->assertSame($stockCountBefore, ProductStock::count());
    }

    public function test_edit_stock_expiry_via_endpoint_does_not_modify_purchase_detail(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchaseExpiry = $this->futureDate(30);
        $purchase = $this->createPurchase([$this->line($productId, '10.000', $purchaseExpiry)]);

        $batch = ProductStock::where('product_id', $productId)->first();
        $newStockExpiry = $this->futureDate(90);

        $this->put("/stock/{$batch->id}", [
            'is_new_batch' => 0,
            'batch_id' => $batch->id,
            'stock_opname' => '10.000',
            'unit_price' => '10000.000',
            'price_consument' => '0.000',
            'price_r1' => '0.000',
            'price_r2' => '0.000',
            'expired_date' => $newStockExpiry,
        ])->assertSessionHasNoErrors();

        $this->assertSame($newStockExpiry, $batch->fresh()->expired_date?->toDateString());
        $this->assertSame(
            $purchaseExpiry,
            $purchase->fresh()->details()->first()->expired_date?->toDateString()
        );
    }

    public function test_delete_purchase_leaves_stock_batch_unchanged(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $purchase = $this->createPurchase([$this->line($productId, '10.000', $this->futureDate(30))]);
        $stockCountBefore = ProductStock::count();

        $this->delete("/purchase/{$purchase->id}")->assertRedirect();

        $this->assertSame($stockCountBefore, ProductStock::count());
        $batch = ProductStock::where('product_id', $productId)->first();
        $this->assertNotNull($batch);
        $this->assertSame($this->futureDate(30), $batch->expired_date?->toDateString());
    }

    public function test_store_rolls_back_purchase_and_details_when_batch_creation_fails(): void
    {
        $this->actingAsOwner();
        $productId = $this->makeProduct();

        $this->partialMock(ProductStockService::class, function ($mock) {
            $mock->shouldReceive('createNewBatch')
                ->once()
                ->andThrow(new \RuntimeException('forced batch failure'));
        });

        $response = $this->post('/purchase', $this->payload([$this->line($productId, '10.000', $this->futureDate(30))]));

        $response->assertStatus(500);
        $this->assertDatabaseCount('product_purchases', 0);
        $this->assertDatabaseCount('product_purchase_details', 0);
        $this->assertDatabaseCount('product_stocks', 0);
    }

    public function test_edit_validation_failure_reopens_edit_not_create(): void
    {
        $this->actingAsOwner();
        $p1 = $this->makeProduct('P-001');
        $p2 = $this->makeProduct('P-002');
        $p3 = $this->makeProduct('P-003');

        $expiryA = $this->futureDate(30);

        $purchase = $this->createPurchase([
            $this->line($p1, '10.000', $expiryA),
            $this->line($p2, '10.000', $this->futureDate(90)),
            $this->line($p3, '10.000', $this->futureDate(180)),
        ]);
        $details = $purchase->details()->get();
        $d0 = $details[0]->id;
        $d2 = $details[2]->id;

        // Baris tengah dihapus (index sparse [0,2]); expiry index 2 dibuat lampau
        // agar validasi EDIT gagal.
        $invalidExpiryC = $this->pastDate(30);
        $response = $this->put("/purchase/{$purchase->id}", $this->payload([
            0 => $this->line($p1, '10.000', $expiryA, $d0),
            2 => $this->line($p3, '10.000', $invalidExpiryC, $d2),
        ]));

        $response->assertSessionHasErrors(['products.2.expired_date']);

        // Index re-render: harus membuka ulang EDIT purchase yang sama, bukan create.
        $indexResponse = $this->get('/purchase');
        $indexResponse->assertOk();
        $html = preg_replace('/\s+/', ' ', $indexResponse->getContent());

        // Edit modal otomatis terbuka, dan create modal/tombol tidak dirender.
        $this->assertStringContainsString("\$dispatch('open-modal', 'edit-purchase')", $html);
        $this->assertStringNotContainsString("\$dispatch('open-modal', 'create-purchase')", $html);

        // Pasangan id <-> expiry dari submit tetap utuh, baris yang dihapus tidak
        // dibangkitkan ulang.
        $this->assertStringContainsString("name=\"products[0][id]\" value=\"{$d0}\"", $html);
        $this->assertStringContainsString("name=\"products[0][expired_date]\" value=\"{$expiryA}\"", $html);
        $this->assertStringContainsString("name=\"products[2][id]\" value=\"{$d2}\"", $html);
        $this->assertStringContainsString("name=\"products[2][expired_date]\" value=\"{$invalidExpiryC}\"", $html);
        $this->assertStringNotContainsString('name="products[1][id]"', $html);

        // Koreksi harus mengarah ke route UPDATE purchase asli (bukan store).
        $this->assertStringContainsString('name="_method" value="PUT"', $html);
        $this->assertStringContainsString('action="'.route('purchase.update', $purchase->id).'"', $html);
        $this->assertStringNotContainsString('action="'.route('purchase.store').'"', $html);

        // Tidak ada purchase baru dibuat akibat kegagalan edit.
        $this->assertSame(1, ProductPurchase::count());
    }

    public function test_stale_edit_context_does_not_recover_into_create(): void
    {
        $this->actingAsOwner();
        $p1 = $this->makeProduct('P-001');
        $p2 = $this->makeProduct('P-002');
        $p3 = $this->makeProduct('P-003');

        $purchase = $this->createPurchase([
            $this->line($p1, '10.000', $this->futureDate(30)),
            $this->line($p2, '10.000', $this->futureDate(90)),
            $this->line($p3, '10.000', $this->futureDate(180)),
        ]);
        $detailId = $purchase->details()->first()->id;

        // 1) Gagalkan validasi EDIT agar marker edit_purchase_id terbentuk.
        $this->put("/purchase/{$purchase->id}", $this->payload([
            $this->line($p1, '10.000', $this->pastDate(30), $detailId),
        ]))->assertSessionHasErrors(['products.0.expired_date']);

        // 2) Purchase menjadi tidak tersedia sebelum index dirender (stale context).
        $purchase->delete();

        // 3) Index tidak boleh jatuh ke alur create dengan old input edit.
        $indexResponse = $this->get('/purchase');
        $indexResponse->assertOk();
        $html = preg_replace('/\s+/', ' ', $indexResponse->getContent());

        $this->assertStringNotContainsString("\$dispatch('open-modal', 'create-purchase')", $html);
        $this->assertStringNotContainsString('id="productForm"', $html);
        $this->assertStringNotContainsString('action="'.route('purchase.update', $purchase->id).'"', $html);

        // 4) Konteks stale dibersihkan.
        $this->assertNull(session('edit_purchase_id'));

        // 5) Alur create yang normal tetap berfungsi setelahnya.
        $this->post('/purchase', $this->payload([$this->line($p1, '10.000', $this->pastDate(30))]))
            ->assertSessionHasErrors(['products.0.expired_date']);

        $createIndex = $this->get('/purchase');
        $createIndex->assertOk();
        $html2 = preg_replace('/\s+/', ' ', $createIndex->getContent());

        $this->assertStringContainsString("\$dispatch('open-modal', 'create-purchase')", $html2);
        $this->assertStringContainsString('id="productForm"', $html2);
        $this->assertStringContainsString('action="'.route('purchase.store').'"', $html2);
    }

    public function test_load_edit_uses_max_plus_one_row_index(): void
    {
        $this->actingAsOwner();
        $this->makeProduct('P-001');

        $response = $this->get('/purchase');
        $response->assertOk();
        $html = $response->getContent();

        // Helper computeNextRowIndex tersedia dan dipakai untuk inisialisasi modal
        // edit, bukan jumlah baris (rows.length) yang bisa meleset pada index sparse.
        $this->assertStringContainsString('function computeNextRowIndex', $html);
        $this->assertStringContainsString('setRowIndex(window.computeNextRowIndex(editModalBody))', $html);
        $this->assertStringNotContainsString('setRowIndex(rows.length)', $html);
    }
}
