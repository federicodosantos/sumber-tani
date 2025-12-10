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

            $fiveLowest = Product::select('products.id','name', 'ps.stock_opname')
            ->join('product_stocks as ps', 'products.id', '=', 'ps.product_id')
            ->whereNull('products.deleted_at')
            ->whereNull('ps.deleted_at')
            ->orderBy('ps.stock_opname', 'asc')->limit(5)->get();

            $totalCategories = ItemCategory::count();

            $mostItemCategory = ItemCategory::withCount('products')->orderBy('products_count', 'desc')->first();

            $leastItemCategory = ItemCategory::withCount('products')->orderBy('products_count', 'asc')->first();

            $summary = compact('totalProducts', 'totalStock', 'fiveLowest', 'totalCategories', 'mostItemCategory', 'leastItemCategory');

            if ($user && $user->isOwner()) {
                $now = Carbon::now();
                
                $monthlySales = Transaction::whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)->sum('total_price');
                
                
                $summary['monthlyIncome'] = $monthlySales;
            } else {
                $summary['monthlyIncome'] = null;
            }

            return $summary;
        } catch (\Exception $e) {
            throw new Exception('Failed to retrieve dashboard summary: ' . $e->getMessage());
        }
    }
}
