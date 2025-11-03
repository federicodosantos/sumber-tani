<?php

use App\Http\Controllers\ItemCategoryController;
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
    Route::get('/product', function () {
        return view('product');
    })->name('product');

    // ITEM CATEGORY ROUTES
    Route::get('/item-category', [ItemCategoryController::class, 'index'])->name('item-category');
    Route::get('/item-category/create', [ItemCategoryController::class, 'create'])->name('item-category.create');
    Route::post('/item-category', [ItemCategoryController::class, 'store'])->name('item-category.store');
    Route::get('/item-category/{itemCategory}/edit', [ItemCategoryController::class, 'edit'])->name('item-category.edit');
    Route::put('/item-category/{itemCategory}', [ItemCategoryController::class, 'update'])->name('item-category.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
