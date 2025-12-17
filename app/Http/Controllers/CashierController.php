<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    public function index(Request $request)
    {
        $categories = ItemCategory::all();

        $categoryId = $request->query('category');
        $search = $request->query('search');

        if (!$categoryId && !$search && $categories->isNotEmpty()) {
            return redirect()->route('cashier', [
                'category' => $categories->first()->id,
            ]);
        }

        /**
         * =========================
         * SUBQUERY: TOTAL STOK
         * =========================
         */
        $totalStockSub = DB::table('product_stocks')->select('product_id', DB::raw('SUM(stock_opname) as total_stock'))->whereNull('deleted_at')->groupBy('product_id');

        /**
         * =========================
         * SUBQUERY: BATCH TERAKHIR (HARGA)
         * =========================
         */
        $latestBatchSub = DB::table('product_stocks as ps1')->select('ps1.product_id', 'ps1.price_consument', 'ps1.price_r1', 'ps1.price_r2')->whereNull('ps1.deleted_at')->whereRaw('ps1.batch = (
        SELECT MAX(ps2.batch)
        FROM product_stocks ps2
        WHERE ps2.product_id = ps1.product_id
        AND ps2.deleted_at IS NULL
    )');

        /**
         * =========================
         * MAIN QUERY
         * =========================
         */
        $products = Product::query()
            ->leftJoinSub($totalStockSub, 'ts', 'ts.product_id', '=', 'products.id')
            ->leftJoinSub($latestBatchSub, 'lb', 'lb.product_id', '=', 'products.id')
            ->join('item_categories as ic', 'products.item_category_id', '=', 'ic.id')
            ->select(['products.id', 'products.name', 'products.description', 'ic.name as category_name', DB::raw('COALESCE(ts.total_stock, 0) as stock_opname'), 'lb.price_consument', 'lb.price_r1', 'lb.price_r2'])

            ->whereNull('products.deleted_at')

            // FILTER CATEGORY
            ->when($categoryId && !$search, function ($query) use ($categoryId) {
                $query->where('products.item_category_id', $categoryId);
            })

            // SEARCH
            ->when($search, function ($query, $search) {
                $query->where('products.name', 'like', "%{$search}%");
            })

            // SORTING
            ->when(
                $request->query('sort'),
                function ($query, $sort) {
                    switch ($sort) {
                        case 'price_low':
                            $query->orderBy('price', 'asc');
                            break;
                        case 'price_high':
                            $query->orderBy('price', 'desc');
                            break;
                        case 'stock_low':
                            $query->orderBy('stock_opname', 'asc');
                            break;
                        case 'stock_high':
                            $query->orderBy('stock_opname', 'desc');
                            break;
                        case 'name_za':
                            $query->orderBy('products.name', 'desc');
                            break;
                        default:
                            $query->orderBy('products.name', 'asc');
                    }
                },
                function ($query) {
                    $query->orderBy('products.name', 'asc');
                },
            )
            ->get();

        return view('cashier.index', compact('categories', 'products'));
    }
}
