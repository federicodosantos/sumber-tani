<?php

use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Models\ItemCategory;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/test', function () {
    return view('tutorial-pakai-template.test-content');
});

Route::get('/create', function () {
    return view('tutorial-pakai-template.test-create-update');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // ITEM CATEGORY ROUTES
    Route::get('/item-category', [ItemCategoryController::class, 'index'])->name('item-category');
    Route::get('/item-category/create', [ItemCategoryController::class, 'create'])->name('item-category.create');
    Route::post('/item-category', [ItemCategoryController::class, 'store'])->name('item-category.store');
    Route::get('/item-category/{itemCategory}/edit', [ItemCategoryController::class, 'edit'])->name('item-category.edit');
    Route::put('/item-category/{itemCategory}', [ItemCategoryController::class, 'update'])->name('item-category.update');
    Route::delete('/item-category/{itemCategory}', [ItemCategoryController::class, 'destroy'])->name('item-category.destroy');

    // PRODUCT ROUTES
    Route::get('/product', [ProductController::class, 'index'])->name('product');
    Route::get('/product/create', [ProductController::class, 'create'])->name('product.create');
    Route::post('/product', [ProductController::class, 'store'])->name('product.store');
    Route::get('/product/{product}/edit', [ProductController::class, 'edit'])->name('product.edit');
    Route::put('/product/{product}', [ProductController::class, 'update'])->name('product.update');
    Route::delete('/product/{product}', [ProductController::class, 'destroy'])->name('product.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
