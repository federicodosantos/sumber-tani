<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\Product;
use Illuminate\Http\Request;

class CashierController extends Controller
{
    public function index(Request $request)
    {
        $categories = ItemCategory::all();
        
        $categoryId = $request->query('category');
        $search = $request->query('search');

        if (!$categoryId && !$search && $categories->isNotEmpty()) {
            $firstCategoryId = $categories->first()->id;
            return redirect()->route('cashier', ['category' => $firstCategoryId]);
        }

        $products = Product::select(
            'products.id',
            'products.name',
            'products.description',
            'ps.stock_opname',
            'ps.price',
            'ic.name as category_name'
        )
            ->join('product_stocks as ps', 'products.id', '=', 'ps.product_id')
            ->join('item_categories as ic', 'products.item_category_id', '=', 'ic.id')
            
            ->when($categoryId && !$search, function ($query) use ($categoryId) {
                return $query->where('products.item_category_id', $categoryId);
            })

            // Logic Search
            ->when($search, function ($query, $search) {
                return $query->where('products.name', 'like', "%{$search}%");
            })

            // Logic Sorting
            ->when($request->query('sort'), function ($query, $sort) {
                switch ($sort) {
                    case 'price_low': return $query->orderBy('ps.price', 'asc');
                    case 'price_high': return $query->orderBy('ps.price', 'desc');
                    case 'stock_low': return $query->orderBy('ps.stock_opname', 'asc');
                    case 'stock_high': return $query->orderBy('ps.stock_opname', 'desc');
                    case 'name_za': return $query->orderBy('products.name', 'desc');
                    default: return $query->orderBy('products.name', 'asc');
                }
            }, function ($query) {
                return $query->orderBy('products.name', 'asc');
            })
            
            ->whereNull('products.deleted_at')
            ->whereNull('ps.deleted_at')
            ->get();

        return view('cashier.index', compact('categories', 'products'));
    }
}