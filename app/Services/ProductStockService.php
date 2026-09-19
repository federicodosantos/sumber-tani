<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductStockService
{
    /**
     * Create new batch for a product
     *
     * @param  array  $data  - ['stock_opname', 'price_consument', 'price_r1', 'price_r2', 'expired_date']
     */
    public function createNewBatch(int $productId, array $data): ProductStock
    {
        // Hitung batch selanjutnya
        $nextBatch = $this->getNextBatchNumber($productId);

        $insertData = [
            'product_id' => $productId,
            'batch' => $nextBatch,
            'stock_opname' => $data['stock_opname'],
            'price_consument' => $data['price_consument'],
            'price_r1' => $data['price_r1'],
            'price_r2' => $data['price_r2'],
            'expired_date' => $data['expired_date'],
        ];

        if (isset($data['unit_price'])) {
            $insertData['unit_price'] = $data['unit_price'];
        }

        return ProductStock::create($insertData);
    }

    /**
     * Update existing batch
     */
    public function updateBatch(int $batchId, array $data): ProductStock
    {
        $stock = ProductStock::findOrFail($batchId);

        $updateData = [
            'stock_opname' => $data['stock_opname'],
            'price_consument' => $data['price_consument'],
            'price_r1' => $data['price_r1'],
            'price_r2' => $data['price_r2'],
            'expired_date' => $data['expired_date'],
        ];

        if (isset($data['unit_price'])) {
            $updateData['unit_price'] = $data['unit_price'];
        }

        $stock->update($updateData);

        return $stock->fresh();
    }

    /**
     * Delete a stock batch
     */
    public function deleteBatch(int $stockId): bool
    {
        $stock = ProductStock::findOrFail($stockId);

        return $stock->delete();
    }

    /**
     * Get next batch number for a product
     */
    public function getNextBatchNumber(int $productId): int
    {
        $lastBatch = ProductStock::where('product_id', $productId)
            ->max('batch');

        return ($lastBatch ?? 0) + 1;
    }

    /**
     * Get product ID from stock ID
     */
    public function getProductIdFromStock(int $stockId): int
    {
        $stock = ProductStock::findOrFail($stockId);

        return $stock->product_id;
    }

    public function prepareStockData(array $validated): array
    {
        $data = [
            'stock_opname' => $validated['stock_opname'],
            'price_consument' => $validated['price_consument'],
            'price_r1' => $validated['price_r1'],
            'price_r2' => $validated['price_r2'],
            'expired_date' => $validated['expired_date'] ?? null,
        ];

        if (isset($validated['unit_price'])) {
            $data['unit_price'] = $validated['unit_price'];
        }

        return $data;
    }

    /**
     * Get active stock for edit page
     * Handles batch selection logic
     */
    public function getActiveStock(int $stockId, ?int $batchId = null, bool $isNewBatch = false): ProductStock
    {
        $stock = ProductStock::with('product:id,code_id,name')
            ->where('id', $stockId)
            ->firstOrFail();

        $productId = $stock->product_id;

        // Mode: New Batch
        if ($isNewBatch) {
            $lastBatchNumber = ProductStock::where('product_id', $productId)->max('batch');

            $activeStock = new ProductStock([
                'product_id' => $productId,
                'batch' => $lastBatchNumber + 1,
                'stock_opname' => 0,
                'unit_price' => 0,
                'price_consument' => 0,
                'price_r1' => 0,
                'price_r2' => 0,
            ]);

            $activeStock->setRelation('product', $stock->product);

            return $activeStock;
        }

        // Mode: Specific Batch
        if ($batchId) {
            return ProductStock::with('product:id,code_id,name')
                ->where('id', $batchId)
                ->where('product_id', $productId)
                ->firstOrFail();
        }

        // Mode: Latest Batch (default)
        return ProductStock::with('product:id,code_id,name')
            ->where('product_id', $productId)
            ->orderBy('batch', 'desc')
            ->firstOrFail();
    }

    /**
     * Get all batches for a product
     */
    public function getBatchesForProduct(int $productId)
    {
        return ProductStock::where('product_id', $productId)
            ->orderBy('batch', 'asc')
            ->get();
    }

    /**
     * Get latest batch selling prices for a product.
     * Returns zero pricing when no batch exists yet.
     */
    public function getLatestBatchPrices(int $productId): array
    {
        $latestBatch = ProductStock::where('product_id', $productId)
            ->whereNull('deleted_at')
            ->orderByDesc('batch')
            ->first();

        if (! $latestBatch) {
            return [
                'price_consument' => '0.000',
                'price_r1' => '0.000',
                'price_r2' => '0.000',
            ];
        }

        return [
            'price_consument' => (string) $latestBatch->price_consument,
            'price_r1' => (string) $latestBatch->price_r1,
            'price_r2' => (string) $latestBatch->price_r2,
        ];
    }

    /**
     * Alokasikan quantity secara FIFO melintasi batch produk dan kurangi stok.
     *
     * Harus dipanggil di dalam transaction database. Baris batch dikunci
     * dengan lockForUpdate() untuk mencegah oversell pada operasi concurrent.
     *
     * @return array<int, array{stock_id: int, quantity: string, unit_price: string}>
     *
     * @throws RuntimeException ketika total stok semua batch tidak mencukupi.
     */
    public function allocateStockFifo(int $productId, string $quantity): array
    {
        $math = app(DecimalMathService::class);

        $batches = ProductStock::where('product_id', $productId)
            ->whereNull('deleted_at')
            ->where('stock_opname', '>', 0)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        $available = $math->round((string) $batches->sum('stock_opname'));

        if ($math->compare($available, $quantity) < 0) {
            throw new RuntimeException(
                'Stok tidak cukup untuk produk ID '.$productId.'. Tersedia '.$available.'.'
            );
        }

        $remaining = $quantity;
        $allocations = [];

        foreach ($batches as $batch) {
            if ($math->compare($remaining, 0) <= 0) {
                break;
            }

            $batchQty = $math->round((string) $batch->stock_opname);
            $take = $math->compare($remaining, $batchQty) <= 0
                ? $remaining
                : $batchQty;

            $batch->decrement('stock_opname', (float) $take);

            $allocations[] = [
                'stock_id' => (int) $batch->id,
                'quantity' => $take,
                'unit_price' => $math->round((string) $batch->unit_price),
            ];

            $remaining = $math->subtract($remaining, $take);
        }

        return $allocations;
    }

    /**
     * Daftar batch yang harga belinya (unit_price/HPP) masih 0/kosong.
     *
     * Kriteria: unit_price NULL/0 AND deleted_at NULL.
     * Urut stok terbesar dulu agar koreksi berdampak besar dikerjakan pertama.
     *
     * @param  'in_stock'|'empty'  $tab
     */
    public function getIncompleteBatches(?string $search = null, string $tab = 'in_stock', int $perPage = 20)
    {
        $query = ProductStock::with('product:id,code_id,name')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('unit_price')->orWhere('unit_price', '<=', 0);
            });

        if ($tab === 'empty') {
            $query->where('stock_opname', '<=', 0);
        } else {
            $query->where('stock_opname', '>', 0);
        }

        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code_id', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('stock_opname', 'desc')->paginate($perPage);
    }

    /**
     * Harga beli terakhir per produk sebagai saran pengisian HPP.
     *
     * Definisi: detail pembelian terakhir produk (purchase_date DESC, id DESC).
     * Kandidat: net_price jika > 0, kalau tidak price jika > 0,
     * kalau tidak → tidak ada saran (kembalikan null).
     *
     * Satu query untuk semua product_id (tanpa N+1), seleksi di PHP.
     *
     * @param  int[]  $productIds
     * @return array<int, array{price: string, date: ?string}|null>
     */
    public function getLatestPurchasePriceMap(array $productIds): array
    {
        $result = array_fill_keys($productIds, null);

        if (empty($productIds)) {
            return $result;
        }

        $math = app(DecimalMathService::class);

        $rows = DB::table('product_purchase_details as d')
            ->join('product_purchases as p', 'p.id', '=', 'd.product_purchase_id')
            ->whereIn('d.product_id', $productIds)
            ->orderBy('p.purchase_date', 'desc')
            ->orderBy('d.id', 'desc')
            ->select([
                'd.product_id',
                'd.net_price',
                'd.price',
                'p.purchase_date',
            ])
            ->get();

        foreach ($rows as $row) {
            $pid = (int) $row->product_id;

            if ($result[$pid] !== null) {
                continue;
            }

            $candidate = null;
            if ($math->compare((string) $row->net_price, 0) > 0) {
                $candidate = $math->round((string) $row->net_price);
            } elseif ($math->compare((string) $row->price, 0) > 0) {
                $candidate = $math->round((string) $row->price);
            }

            if ($candidate !== null) {
                $result[$pid] = [
                    'price' => $candidate,
                    'date' => $row->purchase_date,
                ];
            }
        }

        return $result;
    }

    /**
     * Update harga beli satu batch (dipakai halaman bulk-edit).
     *
     * Sengaja per-model (bukan mass update) agar Spatie LogsActivity
     * tetap mencatat perubahan beserta role pelakunya.
     */
    public function updateBulkUnitPrice(int $stockId, string $unitPrice): ProductStock
    {
        $stock = ProductStock::whereNull('deleted_at')->findOrFail($stockId);
        $stock->update(['unit_price' => $unitPrice]);

        return $stock->fresh();
    }

    /**
     * Posisi persediaan gudang saat ini untuk PDF aset barang.
     *
     * Hanya batch dengan qty sisa > 0. Nilai baris = qty × HPP (`unit_price`),
     * sama dengan pos Persediaan di neraca.
     *
     * @return array{rows: list<array<string, mixed>>, total_value: string, incomplete_count: int}
     */
    public function getOnHandInventoryReport(): array
    {
        $math = app(DecimalMathService::class);

        $batches = ProductStock::query()
            ->with(['product:id,code_id,name,item_category_id', 'product.category:id,name'])
            ->whereNull('deleted_at')
            ->where('stock_opname', '>', 0)
            ->get()
            ->sortBy([
                fn (ProductStock $batch) => mb_strtolower((string) ($batch->product?->category?->name ?? '')),
                fn (ProductStock $batch) => mb_strtolower((string) ($batch->product?->name ?? '')),
                fn (ProductStock $batch) => (int) $batch->batch,
            ])
            ->values();

        $rows = [];
        $totalValue = '0.000';
        $incompleteCount = 0;

        foreach ($batches as $batch) {
            $qty = $math->round((string) $batch->stock_opname);
            $hpp = $math->round((string) ($batch->unit_price ?? 0));
            $missingHpp = ! $math->isPositive($hpp);
            if ($missingHpp) {
                $incompleteCount++;
            }

            $value = $math->multiply($qty, $hpp);
            $totalValue = $math->add($totalValue, $value);

            $rows[] = [
                'code' => $batch->product?->code_id ?? '-',
                'name' => $batch->product?->name ?? 'Produk tidak diketahui',
                'category' => $batch->product?->category?->name ?? '-',
                'batch' => $batch->batch,
                'quantity' => $qty,
                'unit_price' => $hpp,
                'value' => $value,
                'price_consument' => $math->round((string) ($batch->price_consument ?? 0)),
                'price_r1' => $math->round((string) ($batch->price_r1 ?? 0)),
                'price_r2' => $math->round((string) ($batch->price_r2 ?? 0)),
                'expired_date' => $batch->expired_date,
                'missing_hpp' => $missingHpp,
            ];
        }

        return [
            'rows' => $rows,
            'total_value' => $totalValue,
            'incomplete_count' => $incompleteCount,
        ];
    }

    /**
     * Statistik batch tanpa HPP untuk banner neraca & progres bulk-edit.
     *
     * @return array{batch_count: int, stock_qty: string, empty_count: int}
     */
    public function getIncompleteStockStats(): array
    {
        $base = DB::table('product_stocks')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('unit_price')->orWhere('unit_price', '<=', 0);
            });

        return [
            'batch_count' => (int) (clone $base)->where('stock_opname', '>', 0)->count(),
            'stock_qty' => (string) (clone $base)->where('stock_opname', '>', 0)->sum('stock_opname'),
            'empty_count' => (int) (clone $base)->where('stock_opname', '<=', 0)->count(),
        ];
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $totalStock = DB::table('product_stocks')
            ->whereNull('deleted_at')
            ->sum('stock_opname');

        $topProduct = DB::table('product_stocks as ps')
            ->join('products', 'products.id', '=', 'ps.product_id')
            ->whereNull('ps.deleted_at')
            ->select(
                'products.name',
                DB::raw('SUM(ps.stock_opname) as total_stock')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_stock')
            ->first();

        return [
            'total_stock' => $totalStock,
            'top_product' => $topProduct,
        ];
    }

    /**
     * Get paginated stock list with all aggregations
     */
    public function getStockList(?string $search = null, ?string $sort = null, int $perPage = 10)
    {
        // =========================
        // SUBQUERY: TOTAL STOK
        // =========================
        $totalStockSub = DB::table('product_stocks')
            ->select('product_id', DB::raw('SUM(stock_opname) as total_stock'))
            ->whereNull('deleted_at')
            ->groupBy('product_id');

        // =========================
        // SUBQUERY: BATCH TERAKHIR (HARGA)
        // =========================
        $latestBatchSub = DB::table('product_stocks as ps1')
            ->select(
                'ps1.product_id',
                'ps1.id as latest_stock_id',
                'ps1.price_consument',
                'ps1.price_r1',
                'ps1.price_r2'
            )
            ->whereNull('ps1.deleted_at')
            ->whereRaw('ps1.batch = (
                SELECT MAX(ps2.batch)
                FROM product_stocks ps2
                WHERE ps2.product_id = ps1.product_id
                AND ps2.deleted_at IS NULL
            )');

        // =========================
        // SUBQUERY: EXPIRED TERDEKAT
        // =========================
        $nearestExpirySub = DB::table('product_stocks as ps1')
            ->select(
                'ps1.product_id',
                'ps1.batch as expiry_batch',
                'ps1.expired_date'
            )
            ->whereNotNull('ps1.expired_date')
            ->whereNull('ps1.deleted_at')
            ->whereRaw('ps1.expired_date = (
                SELECT MIN(ps2.expired_date)
                FROM product_stocks ps2
                WHERE ps2.product_id = ps1.product_id
                AND ps2.expired_date IS NOT NULL
                AND ps2.deleted_at IS NULL
            )');

        // =========================
        // MAIN QUERY
        // =========================
        $query = Product::query()
            ->leftJoinSub($totalStockSub, 'ts', 'ts.product_id', '=', 'products.id')
            ->leftJoinSub($latestBatchSub, 'lb', 'lb.product_id', '=', 'products.id')
            ->leftJoinSub($nearestExpirySub, 'ne', 'ne.product_id', '=', 'products.id')
            ->select([
                'products.id',
                'products.id as product_id',
                'products.code_id',
                'products.name',
                DB::raw('COALESCE(ts.total_stock, 0) as stock_total'),
                'lb.price_consument',
                'lb.latest_stock_id',
                'lb.price_r1',
                'lb.price_r2',
                'ne.expired_date',
                'ne.expiry_batch',
            ]);

        // =========================
        // SEARCH
        // =========================
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.code_id', 'like', "%{$search}%");
            });
        }

        // =========================
        // SORTING
        // =========================
        $this->applySorting($query, $sort);

        return $query->paginate($perPage);
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, ?string $sort): void
    {
        switch ($sort) {
            case 'product_code_asc':
                $query->orderBy('products.code_id', 'asc');
                break;
            case 'product_code_desc':
                $query->orderBy('products.code_id', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('products.name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('products.name', 'desc');
                break;
            case 'stock_asc':
                $query->orderBy('stock_total', 'asc');
                break;
            case 'stock_desc':
                $query->orderBy('stock_total', 'desc');
                break;
            case 'price_asc':
                $query->orderBy('lb.price_consument', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('lb.price_consument', 'desc');
                break;
            case 'expired_asc':
                $query->orderByRaw('
                    CASE
                        WHEN ne.expired_date IS NULL THEN 1
                        WHEN ne.expired_date < CURDATE() THEN 2
                        ELSE 0
                    END
                ')
                    ->orderBy('ne.expired_date', 'asc');
                break;
            default:
                $query->orderBy('products.code_id', 'asc');
        }
    }
}
