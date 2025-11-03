<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use Illuminate\Http\Request;

class ItemCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ItemCategory::orderBy('name', 'asc')->get();

        return view('item-category.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('item-category.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $isExist = ItemCategory::where('name', $validated['name'])->exists();
        if ($isExist) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['name' => 'Kategori dengan nama tersebut sudah ada.']);
        }

        ItemCategory::create($validated);

        return redirect()->route('item-category')->with('success', 'Item category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ItemCategory $itemCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ItemCategory $itemCategory)
    {
        return view('item-category.edit', compact('itemCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ItemCategory $itemCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $isExist = ItemCategory::where('name', $validated['name'])
            ->where('id', '!=', $itemCategory->id)
            ->exists();

        if ($isExist) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['name' => 'Kategori dengan nama tersebut sudah ada.']);
        }

        $itemCategory->update($validated);

        return redirect()->route('item-category')->with('success', 'Item category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ItemCategory $itemCategory)
    {
        $itemCategory->delete();

        return redirect()->route('item-category')->with('success', 'Item category deleted successfully.');
    }

    public function getAllCategoriesName()
    {
        $categories = ItemCategory::orderBy('name', 'asc')->pluck('name');

        return response()->json($categories);
    }
}
