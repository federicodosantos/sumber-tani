<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\ProductStock;
use App\Models\User;
use App\Services\ProductStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bulk-edit HPP + penutupan keran input stok baru.
 *
 * - Halaman /stock/harga-beli dapat diakses OWNER & EMPLOYEE (persetujuan klien).
 * - Saran prefill: net_price > 0 → price > 0 → null (isi manual).
 * - Update per-model agar LogsActivity (+ kolom role) tetap tercatat.
 * - unit_price wajib gt:0 di create/update reguler maupun bulk.
 */
class BulkBuyingPriceTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $code = 'P-001'): Product
    {
        $category = ItemCategory::create(['name' => 'Kategori Test '.$code]);

        return Product::create([
            'code_id' => $code,
            'name' => 'Produk '.$code,
            'item_category_id' => $category->id,
        ]);
    }

    private function makeBatch(Product $product, string $stock = '10.000', string $unitPrice = '0.000', int $batch = 1): ProductStock
    {
        return ProductStock::create([
            'product_id' => $product->id,
            'batch' => $batch,
            'stock_opname' => $stock,
            'unit_price' => $unitPrice,
            'price_consument' => '20000.000',
            'price_r1' => '19000.000',
            'price_r2' => '18000.000',
            'expired_date' => null,
        ]);
    }

    private function makePurchase(Product $product, string $date, string $net, string $price): void
    {
        $purchase = ProductPurchase::create([
            'purchase_date' => $date,
            'total_items' => '10.000',
            'subtotal' => '100000.000',
            'discount_type' => 'nominal',
            'discount_percent' => '0.000',
            'discount_value' => '0.000',
            'ppn_type' => 'nominal',
            'ppn_percent' => '0.000',
            'ppn_value' => '0.000',
            'grand_total' => '100000.000',
            'payment_method' => 'cash',
            'is_paid' => true,
        ]);

        $purchase->details()->create([
            'product_id' => $product->id,
            'product_code' => $product->code_id,
            'product_name' => $product->name,
            'unit' => 'PCS',
            'het_price' => $price,
            'basic_discount' => '0.000',
            'additional_discount' => '0.000',
            'net_price' => $net,
            'price' => $price,
            'quantity' => '10.000',
            'subtotal' => '100000.000',
            'expired_date' => null,
        ]);
    }

    public function test_bulk_page_accessible_by_owner_and_employee(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'OWNER']))
            ->get(route('stock.bulk.edit'))
            ->assertOk();
    }

    public function test_bulk_page_accessible_by_employee(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'EMPLOYEE']))
            ->get(route('stock.bulk.edit'))
            ->assertOk();
    }

    public function test_bulk_page_redirects_guest_to_login(): void
    {
        $this->get(route('stock.bulk.edit'))->assertRedirect('/login');
    }

    public function test_bulk_update_saves_and_logs_activity_with_role(): void
    {
        $product = $this->makeProduct();
        $batch = $this->makeBatch($product);

        $this->actingAs(User::factory()->create(['role' => 'EMPLOYEE']))
            ->putJson(route('stock.bulk.update', $batch->id), ['unit_price' => '15000.500'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('product_stocks', [
            'id' => $batch->id,
            'unit_price' => '15000.500',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'product-stock',
            'subject_type' => ProductStock::class,
            'subject_id' => $batch->id,
            'role' => 'EMPLOYEE',
        ]);
    }

    public function test_bulk_update_rejects_zero_and_negative(): void
    {
        $product = $this->makeProduct();
        $batch = $this->makeBatch($product);

        foreach (['0.000', '-5.000'] as $bad) {
            $this->actingAs(User::factory()->create(['role' => 'OWNER']))
                ->putJson(route('stock.bulk.update', $batch->id), ['unit_price' => $bad])
                ->assertStatus(422);
        }

        $this->assertDatabaseHas('product_stocks', [
            'id' => $batch->id,
            'unit_price' => '0.000',
        ]);
    }

    public function test_soft_deleted_batch_excluded_and_not_updatable(): void
    {
        $product = $this->makeProduct();
        $batch = $this->makeBatch($product);
        $batch->delete();

        $this->actingAs(User::factory()->create(['role' => 'OWNER']))
            ->get(route('stock.bulk.edit'))
            ->assertOk()
            ->assertDontSee('BATCH '.$batch->batch, false);

        $this->actingAs(User::factory()->create(['role' => 'OWNER']))
            ->putJson(route('stock.bulk.update', $batch->id), ['unit_price' => '10000.000'])
            ->assertNotFound();
    }

    public function test_suggestion_prefers_net_then_price_then_null(): void
    {
        $withNet = $this->makeProduct('P-NET');
        $netZero = $this->makeProduct('P-ZERO');
        $noHistory = $this->makeProduct('P-NONE');

        $this->makePurchase($withNet, '2026-09-11', '19599.802', '19599.802');
        $this->makePurchase($netZero, '2026-07-05', '0.000', '45000.000');

        $map = app(ProductStockService::class)->getLatestPurchasePriceMap([
            $withNet->id, $netZero->id, $noHistory->id,
        ]);

        $this->assertSame('19599.802', $map[$withNet->id]['price']);
        $this->assertSame('45000.000', $map[$netZero->id]['price']);
        $this->assertNull($map[$noHistory->id]);
    }

    public function test_incomplete_stats_counts_only_zero_hpp(): void
    {
        $product = $this->makeProduct();
        $this->makeBatch($product, '10.000', '0.000', 1); // berstok tanpa HPP → hitung
        $this->makeBatch($product, '0.000', '0.000', 2); // kosong tanpa HPP → tab sekunder
        $this->makeBatch($product, '5.000', '12000.000', 3); // sudah ada HPP → abaikan
        $deleted = $this->makeBatch($product, '7.000', '0.000', 4);
        $deleted->delete(); // soft-deleted → abaikan

        $stats = app(ProductStockService::class)->getIncompleteStockStats();

        $this->assertSame(1, $stats['batch_count']);
        $this->assertSame(1, $stats['empty_count']);
    }

    public function test_store_and_update_reject_zero_hpp(): void
    {
        $product = $this->makeProduct();

        $payload = [
            'product_id' => $product->id,
            'unit_price' => '0.000',
            'stock_opname' => '10.000',
            'price_consument' => '20000.000',
            'price_r1' => '19000.000',
            'price_r2' => '18000.000',
        ];

        $this->actingAs(User::factory()->create(['role' => 'EMPLOYEE']))
            ->post(route('stock.store'), $payload)
            ->assertSessionHasErrors('unit_price');

        $this->assertDatabaseMissing('product_stocks', ['product_id' => $product->id]);

        $batch = $this->makeBatch($product, '10.000', '12000.000');

        $this->actingAs(User::factory()->create(['role' => 'OWNER']))
            ->put(route('stock.update', $batch->id), [
                'is_new_batch' => false,
                'batch_id' => $batch->id,
                'unit_price' => '0.000',
                'stock_opname' => '10.000',
                'price_consument' => '20000.000',
                'price_r1' => '19000.000',
                'price_r2' => '18000.000',
            ])
            ->assertSessionHasErrors('unit_price');
    }
}
