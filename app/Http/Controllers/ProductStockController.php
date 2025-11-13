<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Validation\Rule;

class ProductStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allowedSorts = [
            'product_id'   => 'products.id',
            'name'         => 'products.name',
            'stock_opname' => 'ps.stock_opname',
            'price'        => 'ps.price',
        ];

        $query = Product::leftJoin('product_stocks as ps', function ($join) {
            $join->on('products.id', '=', 'ps.product_id')
                 ->whereNull('ps.deleted_at'); 
        })
        ->select(
            'products.id as product_id', 
            'products.name', 
            'products.description',
            'ps.id as stock_id',   
            'ps.stock_opname', 
            'ps.price'
        );
    
        if ($request->filled('search')) {
            $search = $request->input('search');
            
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.id', 'like', "%{$search}%")
                  ->orWhere('products.description', 'like', "%{$search}%")
                  ->orWhere('ps.deleted_at', 'null');
            });
        }

        $sortColumn = $request->input('sort', 'name'); 
        $sortDirection = $request->input('direction', 'asc'); 

        $sortColumnDb = $allowedSorts[$sortColumn] ?? 'products.name';

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        $query->orderBy($sortColumnDb, $sortDirection);

        $products = $query->paginate(10)->appends($request->query());

        $totalStock = ProductStock::whereNull('deleted_at')->sum('stock_opname');

        $topProduct = ProductStock::whereNull('product_stocks.deleted_at')
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->select('products.name', 'product_stocks.stock_opname')
            ->orderByDesc('product_stocks.stock_opname')
            ->first();

        return view('product-stock.index', [
            'products'      => $products,
            'totalStock'    => $totalStock,
            'topProduct'    => $topProduct,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $products = Product::select('id', 'name')->get();
        
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
        $validatedData = $request->validate([
            'product_id' => [
                'required',
                'exists:products,id',
                
                Rule::unique('product_stocks', 'product_id')->whereNull('deleted_at')
            ],
            'stock_opname' => 'required|numeric|min:0',
            'price'        => 'required|numeric|min:0',
        ], [
            'product_id.unique' => 'Produk ini sudah memiliki data stok. Gunakan opsi "Ubah Jumlah Stok".'
        ]);

        try {

            ProductStock::create($validatedData);

            return redirect()->route('stock.index')->with('success', 'Stok awal berhasil ditambahkan.');
        
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data stok: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $stock_id)
    {
        $stock = ProductStock::where('id', $stock_id)
        ->with('product')->firstOrFail();
        return view('product-stock.edit', compact('stock'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $stock_id)
    {
        $validatedData = $request->validate([
            'stock_opname' => 'required|numeric|min:0',
            'price'        => 'required|numeric|min:0',
        ]);
        try {

            $stock = ProductStock::where('id', $stock_id)->firstOrFail();

            $stock->update($validatedData);

            return redirect()->route('stock.index')->with('success', 'Stok produk berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data stok: ' . $e->getMessage()])->withInput();
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
