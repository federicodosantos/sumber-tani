<?php

use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductStockController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FinanceReportController;
use App\Http\Controllers\ProductPurchaseController;
use App\Http\Controllers\QzSecurityController;
use App\Http\Controllers\CustomerR2Controller;

Route::get('/refresh-csrf', function () {
    return csrf_token();
});

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/test', function () {
    return view('tutorial-pakai-template.test-content');
});

Route::get('/create', function () {
    return view('tutorial-pakai-template.test-create-update');
});

Route::middleware('auth')->group(function () {
    // DASHBOARD ROUTE
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // CASHIER ROUTE
    Route::get('/cashier', [App\Http\Controllers\CashierController::class, 'index'])->name('cashier');

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

    // PRODUCT BUY ROUTES
    Route::get('/purchase', [ProductPurchaseController::class, 'index'])->name('purchase.index');
    Route::get('/purchase/create', [ProductPurchaseController::class, 'create'])->name('purchase.create');
    Route::post('/purchase', [ProductPurchaseController::class, 'store'])->name('purchase.store');
    Route::get('/purchase/{purchase}/edit', [ProductPurchaseController::class, 'edit'])->name('purchase.edit');
    Route::put('/purchase/{purchase}', [ProductPurchaseController::class, 'update'])->name('purchase.update');
    Route::delete('/purchase/{purchase}', [ProductPurchaseController::class, 'destroy'])->name('purchase.destroy');

    // PRODUCT STOCK ROUTES
    Route::get('/stock', [ProductStockController::class, 'index'])->name('stock.index');
    Route::get('/stock/create', [ProductStockController::class, 'create'])->name('stock.create');
    Route::get('/stock/{stock_id}', [ProductStockController::class, 'edit'])->name('stock.edit');
    Route::put('/stock/{stock_id}', [ProductStockController::class, 'update'])->name('stock.update');
    Route::post('/stock', [ProductStockController::class, 'store'])->name('stock.store');
    Route::delete('/stock/{stock_id}', [ProductStockController::class, 'destroy'])->name('stock.destroy');

    // ACTIVITY LOG ROUTES
    Route::get('/riwayat-aktivitas', [ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::get('/riwayat-aktivitas/{activity}', [ActivityLogController::class, 'show'])->name('activity-log.show');

    // FINANCE REPORT ROUTE
    Route::get('/laporan-keuangan', [FinanceReportController::class, 'index'])->name('finance.index');
    Route::get('/laporan-keuangan/{transaction}', [FinanceReportController::class, 'show'])->name('finance.show');
    Route::post('/laporan-keuangan/download', [FinanceReportController::class, 'download'])->name('finance.download');

    // TRANSACTION ROUTES
    Route::get('/receipt/{id}', [TransactionController::class, 'show']);
    Route::post('/checkout', [TransactionController::class, 'store'])->name('trx.store');
    Route::put('/transactions/{id}/update-status', [TransactionController::class, 'updateStatus'])
        ->name('transactions.updateStatus');

    // CUSTOMER R-2 ROUTES
    Route::get('/customer-r2', [CustomerR2Controller::class, 'index'])->name('customer-r2.index');
    Route::get('/customer-r2/create', [CustomerR2Controller::class, 'create'])->name('customer-r2.create');
    Route::post('/customer-r2', [CustomerR2Controller::class, 'store'])->name('customer-r2.store');
    Route::get('/customer-r2/{customer}', [CustomerR2Controller::class, 'show'])->name('customer-r2.show');
    Route::get('/customer-r2/{customer}/pay', [CustomerR2Controller::class, 'payDebt'])->name('customer-r2.pay');
    Route::post('/customer-r2/{customer}/pay', [CustomerR2Controller::class, 'processPayment'])->name('customer-r2.process');
    Route::get('/api/customer-r2/search', [CustomerR2Controller::class, 'search'])->name('customer-r2.search');
    Route::get('/customer-r2/invoice/{invoice}/preview', [CustomerR2Controller::class, 'previewInvoice'])->name('customer-r2.invoice.preview');
    Route::get('/invoice-receipt/{invoice}', [CustomerR2Controller::class, 'receiptData'])->name('customer-r2.invoice.receipt-data');
    Route::get('/api/customer-r2/{customer}/custom-prices', [CustomerR2Controller::class, 'getCustomPrices'])->name('customer-r2.custom-prices');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/qz/digital-certificate', [QzSecurityController::class, 'certificate']);
Route::post('/qz/sign', [QzSecurityController::class, 'sign']);

Route::get('/test-sign', function () {
    $data = json_encode(['test' => 'hello']);
    $privateKey = openssl_pkey_get_private(file_get_contents(storage_path('app/private/qz/private-key.pem')));
    openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);

    return response()->json([
        'signature' => base64_encode($signature),
        'len' => strlen($signature),
    ]);
});

require __DIR__ . '/auth.php';
