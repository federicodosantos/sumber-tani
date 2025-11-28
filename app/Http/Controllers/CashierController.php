<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\Product;
use Illuminate\Http\Request;

class CashierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categories = ItemCategory::all();

        if (!$request->has('category') && $categories->isNotEmpty()) {
        
            $firstCategoryId = $categories->first()->id;
    
            return redirect()->route('cashier', ['category' => $firstCategoryId]);
        }

        $products = Product::select(
            'products.id',
            'products.name',
            'products.description',
            'ps.stock_opname',
            'ps.price',
            'ic.name as category_name')
            ->join('product_stocks as ps', 'products.id', '=', 'ps.product_id')
            ->join('item_categories as ic', 'products.item_category_id', '=', 'ic.id')
            ->when($request->query('category'), function ($query, $categoryId) {
                return $query->where('item_category_id', $categoryId);
            })
            ->whereNull('products.deleted_at')
            ->whereNull('ps.deleted_at')->get();

        return view('cashier.index', compact('categories', 'products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
