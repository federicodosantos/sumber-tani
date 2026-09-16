<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\ProductStock;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Replikasi kondisi production di database dev: batch stok dengan
 * unit_price/HPP = 0 (warisan pra-fitur April 2026 + lubang berjalan).
 *
 * - DEV-ONLY. Tidak didaftarkan ke DatabaseSeeder (manual-run saja).
 * - Aman production: abort bila APP_ENV=production.
 * - Kompatibel MySQL & SQLite (Eloquent saja, tanpa statement mentah).
 * - Idempoten: baris seeder ditandai timestamp marker & dibuat ulang tiap run.
 *
 * Komposisi 25 batch:
 * - 18 warisan berstok (created_at < 2026-04-19, unit_price 0, stock > 0)
 * - 3 lubang berjalan (created_at now, unit_price 0, stock > 0)
 * - 3 kosong tanpa HPP (tab sekunder)
 * - 1 soft-deleted tanpa HPP (wajib ter-exclude)
 * - 4 riwayat pembelian (3 saran normal + 1 edge net_price=0/price>0)
 *
 * Jalankan: php artisan db:seed --class=DevLegacyStockSeeder
 */
class DevLegacyStockSeeder extends Seeder
{
    private const LEGACY_DATE = '2026-03-10 08:00:00';

    private const PURCHASE_DATES = [
        '2026-09-11',
        '2026-08-20',
        '2026-07-05',
        '2026-09-01',
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('DevLegacyStockSeeder dilarang berjalan di production.');

            return;
        }

        $products = Product::orderBy('id')->get();

        if ($products->count() < 8) {
            $this->command->error('Butuh minimal 8 produk. Jalankan "php artisan db:seed" dulu, baru seeder ini.');

            return;
        }

        $this->resetPreviousRun($products);

        // [productIndex, stock, isLegacy, sellConsument, sellR1, sellR2]
        $batches = [
            // Pupuk Urea — warisan besar + 1 kosong (punya riwayat beli)
            [0, '150.000', true, '22000.000', '21000.000', '20000.000'],
            [0, '80.000', true, '22000.000', '21000.000', '20000.000'],
            [0, '40.000', true, '22000.000', '21000.000', '20000.000'],
            [0, '12.500', true, '22000.000', '21000.000', '20000.000'],
            [0, '0.000', true, '22000.000', '21000.000', '20000.000'],
            // NPK Mutiara — warisan (punya riwayat beli)
            [1, '60.000', true, '18500.000', '17800.000', '17000.000'],
            [1, '25.000', true, '18500.000', '17800.000', '17000.000'],
            [1, '7.250', true, '18500.000', '17800.000', '17000.000'],
            // Roundup — warisan TANPA riwayat beli (isi manual) + 1 lubang berjalan
            [2, '30.000', true, '65000.000', '63000.000', '61000.000'],
            [2, '15.000', true, '65000.000', '63000.000', '61000.000'],
            [2, '5.000', true, '65000.000', '63000.000', '61000.000'],
            [2, '20.000', false, '65000.000', '63000.000', '61000.000'],
            // Gramoxone — warisan (riwayat edge net=0) + 1 lubang berjalan
            [3, '22.000', true, '48000.000', '46500.000', '45000.000'],
            [3, '9.000', true, '48000.000', '46500.000', '45000.000'],
            [3, '11.000', false, '48000.000', '46500.000', '45000.000'],
            // Benih Jagung — warisan (punya riwayat beli)
            [4, '45.000', true, '12500.000', '12000.000', '11500.000'],
            [4, '18.750', true, '12500.000', '12000.000', '11500.000'],
            // Benih Padi — warisan TANPA riwayat beli (isi manual)
            [5, '33.000', true, '14200.000', '13800.000', '13200.000'],
            [5, '6.000', true, '14200.000', '13800.000', '13200.000'],
            // Cangkul — 1 warisan + 1 kosong + 1 lubang berjalan, tanpa riwayat beli
            [6, '14.000', true, '55000.000', '53000.000', '50000.000'],
            [6, '0.000', true, '55000.000', '53000.000', '50000.000'],
            [6, '8.000', false, '55000.000', '53000.000', '50000.000'],
            // Sprayer — 1 warisan + 1 kosong, tanpa riwayat beli
            [7, '10.000', true, '325000.000', '315000.000', '300000.000'],
            [7, '0.000', true, '325000.000', '315000.000', '300000.000'],
        ];

        $legacyAt = Carbon::parse(self::LEGACY_DATE);
        $minute = 0;

        DB::transaction(function () use ($products, $batches, $legacyAt, &$minute) {
            foreach ($batches as [$pIndex, $stock, $isLegacy, $c, $r1, $r2]) {
                $product = $products[$pIndex];
                $nextBatch = (int) ProductStock::withTrashed()
                    ->where('product_id', $product->id)
                    ->max('batch') + 1;

                $timestamp = $isLegacy
                    ? $legacyAt->copy()->addMinutes($minute++)
                    : now();

                ProductStock::create([
                    'product_id' => $product->id,
                    'batch' => $nextBatch,
                    'stock_opname' => $stock,
                    'unit_price' => '0.000',
                    'price_consument' => $c,
                    'price_r1' => $r1,
                    'price_r2' => $r2,
                    'expired_date' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }

            // 1 batch soft-deleted tanpa HPP (wajib ter-exclude dari halaman & banner)
            $product = $products[2];
            $nextBatch = (int) ProductStock::withTrashed()
                ->where('product_id', $product->id)
                ->max('batch') + 1;

            $deleted = ProductStock::create([
                'product_id' => $product->id,
                'batch' => $nextBatch,
                'stock_opname' => '50.000',
                'unit_price' => '0.000',
                'price_consument' => '65000.000',
                'price_r1' => '63000.000',
                'price_r2' => '61000.000',
                'expired_date' => null,
                'created_at' => $legacyAt->copy()->addMinutes($minute++),
                'updated_at' => $legacyAt->copy()->addMinutes($minute++),
            ]);
            $deleted->delete();

            // Riwayat pembelian sebagai bahan saran prefill:
            // [productIndex, purchaseDate, het, basicDisc, addDisc, net, price, qty]
            $this->seedPurchase($products[0], '2026-09-11', '20000.000', '0.000', '400.198', '19599.802', '19599.802', '100.000');
            $this->seedPurchase($products[1], '2026-08-20', '17000.000', '500.000', '0.000', '16500.000', '16500.000', '80.000');
            // Edge: net_price 0 warisan format lama → saran fallback ke price
            $this->seedPurchase($products[3], '2026-07-05', '45000.000', '0.000', '0.000', '0.000', '45000.000', '50.000');
            $this->seedPurchase($products[4], '2026-09-01', '11000.000', '0.000', '0.000', '11000.000', '11000.000', '120.000');
        });

        $this->command->info('DevLegacyStockSeeder: 25 batch HPP-0 + 4 riwayat pembelian dibuat.');
    }

    private function resetPreviousRun($products): void
    {
        $markerStart = Carbon::parse(self::LEGACY_DATE);
        $markerEnd = $markerStart->copy()->addHours(1);

        ProductStock::withTrashed()
            ->where('unit_price', 0)
            ->whereBetween('created_at', [$markerStart, $markerEnd])
            ->whereIn('product_id', $products->pluck('id'))
            ->forceDelete();

        ProductPurchase::whereIn('purchase_date', self::PURCHASE_DATES)->delete();
    }

    private function seedPurchase(
        Product $product,
        string $date,
        string $het,
        string $basicDisc,
        string $addDisc,
        string $net,
        string $price,
        string $qty
    ): void {
        $subtotal = bcmul($net, $qty, 3);

        $purchase = ProductPurchase::create([
            'purchase_date' => $date,
            'total_items' => $qty,
            'subtotal' => $subtotal,
            'discount_type' => 'nominal',
            'discount_percent' => '0.000',
            'discount_value' => '0.000',
            'ppn_type' => 'nominal',
            'ppn_percent' => '0.000',
            'ppn_value' => '0.000',
            'grand_total' => $subtotal,
            'payment_method' => 'cash',
            'is_paid' => true,
        ]);

        $purchase->details()->create([
            'product_id' => $product->id,
            'product_code' => $product->code_id,
            'product_name' => $product->name,
            'unit' => 'PCS',
            'het_price' => $het,
            'basic_discount' => $basicDisc,
            'additional_discount' => $addDisc,
            'net_price' => $net,
            'price' => $price,
            'quantity' => $qty,
            'subtotal' => $subtotal,
            'expired_date' => null,
        ]);
    }
}
