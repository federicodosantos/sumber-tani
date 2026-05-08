<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ItemCategory;
use App\Models\ProductStock;
use Exception;
use App\Models\Transaction;
use Carbon\Carbon;

class DashboardService
{
    public function getSummary($user): array
    {
        try {
            $totalProducts = Product::count();
            $totalStock = ProductStock::sum('stock_opname');

            $fiveLowest = Product::select('products.id', 'products.name', 'ps.stock_opname')->join('product_stocks as ps', 'products.id', '=', 'ps.product_id')->whereNull('products.deleted_at')->whereNull('ps.deleted_at')->orderBy('ps.stock_opname', 'asc')->limit(5)->get();

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
