<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductStockRequest;
use App\Http\Requests\UpdateProductStockRequest;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\ProductStockService;

class ProductStockController extends Controller
{
    protected ProductStockService $stockService;

    public function __construct(ProductStockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get paginated stock list with search and sort
        $products = $this->stockService->getStockList(
            search: $request->input('search'),
            sort: $request->input('sort'),
            perPage: 10
        );

        // Append query params to pagination links
        $products->appends($request->query());

        // Get dashboard statistics
        $stats = $this->stockService->getDashboardStats();

        // Load all batches for each product for the Edit Modal
        $products->load('stock');

        return view('product-stock.index', [
            'products' => $products,
            'totalStock' => $stats['total_stock'],
            'topProduct' => $stats['top_product'],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $products = Product::select('products.id', 'products.code_id', 'products.name', 'ps.unit_price')
            ->leftJoinSub(
                \Illuminate\Support\Facades\DB::table('product_stocks as ps1')
                    ->select('ps1.product_id', 'ps1.unit_price')
                    ->whereRaw('ps1.id = (SELECT id FROM product_stocks ps2 WHERE ps2.product_id = ps1.product_id AND ps2.deleted_at IS NULL ORDER BY ps2.batch DESC LIMIT 1)'),
                'ps',
                'ps.product_id',
                '=',
                'products.id'
            )
            ->get();
        $selectedProductId = $request->query('product_id');

        return view('product-stock.create', [
            'products' => $products,
            'selectedProductId' => $selectedProductId,
            'expiryValue' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductStockRequest $request)
    {
        $validated = $request->validated();

        try {
            $data = $this->stockService->prepareStockData($validated);
            $this->stockService->createNewBatch($validated['product_id'], $data);

            return redirect()
                ->route('stock.index')
                ->with('success', 'Stok awal berhasil ditambahkan.');

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data stok: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, int $stock_id)
    {
        // Determine active stock based on request
        $activeStock = $this->stockService->getActiveStock(
            stockId: $stock_id,
            batchId: $request->input('batch_id'),
            isNewBatch: $request->filled('new_batch')
        );

        // Get all batches for this product
        $batches = $this->stockService->getBatchesForProduct($activeStock->product_id);

        $expiryValue = $activeStock->expired_date 
            ? \Carbon\Carbon::parse($activeStock->expired_date)->format('Y-m-d') 
            : null;
        
        return view('product-stock.edit', [
            'activeStock' => $activeStock,
            'batches' => $batches,
            'expiryValue' => $expiryValue,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductStockRequest $request, int $stock_id)
    {
        $validated = $request->validated();

        try {
            $data = $this->stockService->prepareStockData($validated);

            // Mode: Create New Batch
            if ($validated['is_new_batch']) {
                $productId = $this->stockService->getProductIdFromStock($stock_id);
                $this->stockService->createNewBatch($productId, $data);
                
                $message = 'Batch baru berhasil ditambahkan.';
            } 
            // Mode: Update Existing Batch
            else {
                $this->stockService->updateBatch($validated['batch_id'], $data);
                
                $message = 'Stok batch berhasil diperbarui.';
            }

            return redirect()
                ->route('stock.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $stock_id)
    {
        try {
            $this->stockService->deleteBatch($stock_id);

            return redirect()
                ->route('stock.index')
                ->with('success', 'Stok produk berhasil dihapus.');

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menghapus data stok: ' . $e->getMessage()]);
        }
    }
}
