<?php

namespace Tests\Support;

use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\ProductPurchaseDetail;
use App\Models\User;

/**
 * Helper pembuatan nota pembelian untuk feature test penerimaan barang.
 *
 * Sengaja memakai HTTP post (bukan factory model) supaya jalur validasi +
 * controller + service ikut teruji, sama seperti PurchaseExpirationTest.
 */
trait BuildsPurchases
{
    protected function actingAsOwner(): User
    {
        $user = User::factory()->create(['role' => 'OWNER']);
        $this->actingAs($user);

        return $user;
    }

    protected function makeProduct(string $code = 'P-001'): int
    {
        $category = ItemCategory::firstOrCreate(['name' => 'Kategori Test']);

        $product = Product::create([
            'code_id' => $code,
            'name' => 'Produk '.$code,
            'item_category_id' => $category->id,
        ]);

        return $product->id;
    }

    protected function purchaseDate(): string
    {
        return now()->subDays(5)->toDateString();
    }

    protected function futureDate(int $days = 30): string
    {
        return now()->addDays($days)->toDateString();
    }

    /**
     * Satu baris item nota.
     *
     * @param  bool|null  $directStock  null = key tidak dikirim sama sekali
     *                                  (mensimulasikan klien lama)
     */
    protected function line(
        int $productId,
        string $qty = '10.000',
        ?bool $directStock = true,
        ?string $expiredDate = null,
        $id = null,
        string $hetPrice = '10000.000'
    ): array {
        $line = [
            'id' => $id,
            'product_id' => $productId,
            'het_price' => $hetPrice,
            'basic_discount' => '0.000',
            'additional_discount' => '0.000',
            'quantity' => $qty,
            'unit' => 'PCS',
            'expired_date' => $expiredDate,
        ];

        if ($directStock !== null) {
            $line['direct_stock'] = $directStock ? '1' : '0';
        }

        return $line;
    }

    protected function payload(array $lines): array
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

    protected function createPurchase(array $lines): ProductPurchase
    {
        $this->post('/purchase', $this->payload($lines));

        return ProductPurchase::latest('id')->firstOrFail();
    }

    protected function detailOf(ProductPurchase $purchase, int $index = 0): ProductPurchaseDetail
    {
        return $purchase->details()->orderBy('id')->get()[$index];
    }
}
