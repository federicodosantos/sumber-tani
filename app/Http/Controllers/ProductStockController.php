<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductStock;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ProductStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        // =========================
        // SUBQUERY: TOTAL STOK
        // =========================
        $totalStockSub = DB::table('product_stocks')->select('product_id', DB::raw('SUM(stock_opname) as total_stock'))->whereNull('deleted_at')->groupBy('product_id');

        // =========================
        // SUBQUERY: BATCH TERAKHIR (HARGA)
        // =========================
        $latestBatchSub = DB::table('product_stocks as ps1')->select('ps1.product_id', 'ps1.id as latest_stock_id', 'ps1.price_consument', 'ps1.price_r1', 'ps1.price_r2')->whereNull('ps1.deleted_at')->whereRaw('ps1.batch = (
        SELECT MAX(ps2.batch)
        FROM product_stocks ps2
        WHERE ps2.product_id = ps1.product_id
        AND ps2.deleted_at IS NULL
    )');

        // =========================
        // SUBQUERY: EXPIRED TERDEKAT
        // =========================
        $nearestExpirySub = DB::table('product_stocks as ps1')->select('ps1.product_id', 'ps1.batch as expiry_batch', 'ps1.expired_date')->whereNotNull('ps1.expired_date')->whereNull('ps1.deleted_at')->whereRaw('ps1.expired_date = (
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
            ->select(['products.id as product_id', 'products.code_id', 'products.name', DB::raw('COALESCE(ts.total_stock, 0) as stock_total'), 'lb.price_consument', 'lb.latest_stock_id', 'lb.price_r1', 'lb.price_r2', 'ne.expired_date', 'ne.expiry_batch']);

        // =========================
        // SEARCH
        // =========================
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")->orWhere('products.code_id', 'like', "%{$search}%");
            });
        }

        // =========================
        // SORTING
        // =========================
        switch ($request->get('sort')) {
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
                $query
                    ->orderByRaw(
                        "
                            CASE
                                WHEN ne.expired_date IS NULL THEN 1
                                WHEN ne.expired_date < CURDATE() THEN 2
                                ELSE 0
                            END
                        ",
                                )
                    ->orderBy('ne.expired_date', 'asc');
                break;

            default:
                $query->orderBy('products.code_id', 'asc');
        }

        $products = $query->paginate(10)->appends($request->query());

        // =========================
        // DASHBOARD INFO
        // =========================
        $totalStock = DB::table('product_stocks')->whereNull('deleted_at')->sum('stock_opname');

        $topProduct = DB::table('product_stocks as ps')->join('products', 'products.id', '=', 'ps.product_id')->whereNull('ps.deleted_at')->select('products.name', DB::raw('SUM(ps.stock_opname) as total_stock'))->groupBy('products.id', 'products.name')->orderByDesc('total_stock')->first();

        return view('product-stock.index', compact('products', 'totalStock', 'topProduct'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $products = Product::select('id', 'code_id', 'name')->get();

        $selectedProductId = $request->query('product_id');

        return view('product-stock.create', [
            'products' => $products,
            'selectedProductId' => $selectedProductId,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate(
            [
                'product_id' => ['required', 'exists:products,id', Rule::unique('product_stocks', 'product_id')->whereNull('deleted_at')],
                'stock_opname' => 'required|numeric|min:0',
                'price' => 'required|numeric|min:0',
            ],
            [
                'product_id.unique' => 'Produk ini sudah memiliki data stok. Gunakan opsi "Ubah Jumlah Stok".',
            ],
        );

        try {
            ProductStock::create($validatedData);

            return redirect()->route('stock.index')->with('success', 'Stok awal berhasil ditambahkan.');
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
        $stock = ProductStock::with('product:id,code_id,name')->where('id', $stock_id)->firstOrFail();

        $productId = $stock->product_id;

        $batches = ProductStock::where('product_id', $productId)->orderBy('batch', 'asc')->get();

        // =========================
        // Tentukan active stock
        // =========================
        if ($request->filled('new_batch')) {
            $lastBatchNumber = ProductStock::where('product_id', $productId)->max('batch');

            $activeStock = new ProductStock([
                'product_id' => $productId,
                'batch' => $lastBatchNumber + 1,
                'stock_opname' => 0,
                'price_consument' => 0,
                'price_r1' => 0,
                'price_r2' => 0,
            ]);

            $activeStock->setRelation('product', $stock->product);
        } elseif ($request->filled('batch_id')) {
            $activeStock = ProductStock::with('product:id,code_id,name')->where('id', $request->batch_id)->where('product_id', $productId)->firstOrFail();
        } else {
            $activeStock = ProductStock::with('product:id,code_id,name')->where('product_id', $productId)->orderBy('batch', 'desc')->first();
        }

        // =========================
        // HITUNG EXPIRY UNTUK EDIT
        // =========================
        $expiryValue = 1;
        $expiryUnit = 'days';

        if (!empty($activeStock->expired_date)) {
            $today = Carbon::today();
            $expire = Carbon::parse($activeStock->expired_date);

            $daysDiff = $today->diffInDays($expire, false);

            if ($daysDiff >= 365 && $daysDiff % 365 === 0) {
                $expiryValue = $daysDiff / 365;
                $expiryUnit = 'years';
            } elseif ($daysDiff >= 30 && $daysDiff % 30 === 0) {
                $expiryValue = $daysDiff / 30;
                $expiryUnit = 'months';
            } elseif ($daysDiff >= 7 && $daysDiff % 7 === 0) {
                $expiryValue = $daysDiff / 7;
                $expiryUnit = 'weeks';
            } else {
                $expiryValue = max($daysDiff, 0);
                $expiryUnit = 'days';
            }
        }

        return view('product-stock.edit', [
            'activeStock' => $activeStock,
            'batches' => $batches,
            'expiryValue' => $expiryValue,
            'expiryUnit' => $expiryUnit,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $stock_id)
    {
        $validated = $request->validate([
            'is_new_batch' => 'required|boolean',

            'batch_id' => ['exclude_if:is_new_batch,1', 'required', Rule::exists('product_stocks', 'id')],

            'stock_opname' => 'required|numeric|min:0',
            'price_consument' => 'required|numeric|min:0',
            'price_r1' => 'required|numeric|min:0',
            'price_r2' => 'required|numeric|min:0',

            'expiry_date' => 'nullable|numeric|min:0',
            'expiry_unit' => 'nullable|in:days,weeks,months,years',
        ]);

        try {
            // =========================
            // MODE: TAMBAH BATCH BARU
            // =========================
            if ($validated['is_new_batch']) {
                $baseStock = ProductStock::findOrFail($stock_id);
                $productId = $baseStock->product_id;

                // batch selanjutnya
                $lastBatch = ProductStock::where('product_id', $productId)->max('batch');

                // hitung expired_date
                $expiredDate = null;
                if (!empty($validated['expiry_date']) && !empty($validated['expiry_unit'])) {
                    $expiredDate = now()->add($validated['expiry_unit'], (int) $validated['expiry_date'])->startOfDay();
                }

                $nextBatch = ($lastBatch ?? 0) + 1;

                ProductStock::create([
                    'product_id' => $productId,
                    'batch' => $nextBatch,
                    'stock_opname' => $validated['stock_opname'],
                    'price_consument' => $validated['price_consument'],
                    'price_r1' => $validated['price_r1'],
                    'price_r2' => $validated['price_r2'],
                    'expired_date' => $expiredDate,
                ]);

                return redirect()->route('stock.index')->with('success', 'Batch baru berhasil ditambahkan.');
            }

            // =========================
            // MODE: UPDATE BATCH LAMA
            // =========================
            $stock = ProductStock::findOrFail($validated['batch_id']);

            $expiredDate = null;
            if (!empty($validated['expiry_date']) && !empty($validated['expiry_unit'])) {
                $expiredDate = now()->add($validated['expiry_unit'], (int) $validated['expiry_date'])->startOfDay();
            }

            $stock->update([
                'stock_opname' => $validated['stock_opname'],
                'price_consument' => $validated['price_consument'],
                'price_r1' => $validated['price_r1'],
                'price_r2' => $validated['price_r2'],
                'expired_date' => $expiredDate,
            ]);

            return redirect()->route('stock.index')->with('success', 'Stok batch berhasil diperbarui.');
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
            $stock = ProductStock::findOrFail($stock_id);
            $stock->delete();

            return redirect()->route('stock.index')->with('success', 'Stok produk berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus data stok: ' . $e->getMessage()]);
        }
    }
}
