<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductStockService
{
    /**
     * Create new batch for a product
     * 
     * @param int $productId
     * @param array $data - ['stock_opname', 'price_consument', 'price_r1', 'price_r2', 'expired_date']
     * @return ProductStock
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
            'expired_date' => $data["expired_date"],
        ];

        if (isset($data['unit_price'])) {
            $insertData['unit_price'] = $data['unit_price'];
        }

        return ProductStock::create($insertData);
    }

    /**
     * Update existing batch
     * 
     * @param int $batchId
     * @param array $data
     * @return ProductStock
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

        if (!$latestBatch) {
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
                'ne.expiry_batch'
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
                $query->orderByRaw("
                    CASE
                        WHEN ne.expired_date IS NULL THEN 1
                        WHEN ne.expired_date < CURDATE() THEN 2
                        ELSE 0
                    END
                ")
                ->orderBy('ne.expired_date', 'asc');
                break;
            default:
                $query->orderBy('products.code_id', 'asc');
        }
    }
}
