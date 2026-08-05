<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ItemCategory;
use App\Models\ProductStock;
use Exception;
use App\Models\Transaction;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getSummary($user): array
    {
        try {
            $totalProducts = Product::count();
            $totalStock = ProductStock::sum('stock_opname');

            $totalStockSub = DB::table('product_stocks')
                ->select('product_id', DB::raw('SUM(stock_opname) as total_stock'))
                ->whereNull('deleted_at')
                ->groupBy('product_id');

            $fiveLowest = Product::query()
                ->leftJoinSub($totalStockSub, 'ts', 'ts.product_id', '=', 'products.id')
                ->select([
                    'products.id',
                    'products.name',
                    DB::raw('COALESCE(ts.total_stock, 0) as stock_opname')
                ])
                ->whereNull('products.deleted_at')
                ->orderBy('stock_opname', 'asc')
                ->limit(5)
                ->get();

            $totalCategories = ItemCategory::count();

            $mostItemCategory = ItemCategory::withCount('products')->orderBy('products_count', 'desc')->first();

            $leastItemCategory = ItemCategory::withCount('products')->orderBy('products_count', 'asc')->first();

            /**
             * =========================
             * EXPIRED TERDEKAT (PER PRODUK)
             * =========================
             */
            $nearestExpiredStocks = ProductStock::select('product_stocks.product_id', 'product_stocks.batch', 'product_stocks.expired_date', 'products.name')
                ->join('products', 'products.id', '=', 'product_stocks.product_id')
                ->whereNotNull('product_stocks.expired_date')
                ->whereDate('product_stocks.expired_date', '>=', Carbon::today())
                ->whereNull('product_stocks.deleted_at')
                ->orderBy('product_stocks.expired_date', 'asc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    $item->days_left = Carbon::today()->diffInDays(Carbon::parse($item->expired_date), false);
                    return $item;
                });

            $products = Product::whereNull('deleted_at')->get();
            $categories = ItemCategory::all();

            $summary = compact('totalProducts', 'totalStock', 'fiveLowest', 'totalCategories', 'mostItemCategory', 'leastItemCategory', 'nearestExpiredStocks', 'products', 'categories');

            if ($user && $user->isOwner()) {
                $now = Carbon::now();

                $summary['monthlyIncome'] = Transaction::whereYear('transaction_date', $now->year)->whereMonth('transaction_date', $now->month)->sum('total_price');
            } else {
                $summary['monthlyIncome'] = null;
            }

            return $summary;
        } catch (\Exception $e) {
            throw new \Exception('Failed to retrieve dashboard summary: ' . $e->getMessage());
        }
    }
}
