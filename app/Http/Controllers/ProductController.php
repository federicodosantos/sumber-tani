<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('category')->orderBy('name', 'asc')->get();

        return view('product.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = ItemCategory::orderBy('name', 'asc')->get();

        return view('product.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code_id' => 'required|string|max:50|unique:products,code_id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'item_category_id' => 'required|exists:item_categories,id',
        ]);

        try {
            $isExist = Product::where('code_id', $validated['code_id'])->orWhere('name', $validated['name'])->exists();

            if ($isExist) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['general' => 'ID atau nama produk sudah digunakan.']);
            }

            Product::create($validated);

            return redirect()->route('product')->with('success', 'Product created successfully.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['general' => 'ID atau nama produk sudah digunakan.']);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $categories = ItemCategory::orderBy('name', 'asc')->get();

        return view('product.edit', compact('categories', 'product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'item_category_id' => 'required|exists:item_categories,id',
        ]);
        $isExist = Product::where('name', $validated['name'])->where('code_id', '!=', $product->code_id)->exists();

        if ($isExist) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['name' => 'Product dengan nama tersebut sudah ada.']);
        }

        $product->update($validated);

        return redirect()->route('product')->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $newCodeId = 'DELETED-'.$product->code_id;

        $product->update(['code_id' => $newCodeId]);
        $product->delete();

        return redirect()->route('product')->with('success', 'Product deleted successfully.');
    }
}
