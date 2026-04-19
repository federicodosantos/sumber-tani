<?php

use App\Models\ProductStock;
use App\Models\ProductPurchaseDetail;
use App\Models\TransactionDetail;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting backfill...\n";

// Backfill ProductStock unit_price
$stocks = ProductStock::all();
foreach ($stocks as $ps) {
    if ($ps->unit_price == 0) {
        $latestPrice = ProductPurchaseDetail::where('product_id', $ps->product_id)->latest()->value('price');
        if ($latestPrice) {
            $ps->update(['unit_price' => $latestPrice]);
            echo "Updated Stock ID {$ps->id} for Product ID {$ps->product_id} with unit_price {$latestPrice}\n";
        }
    }
}

// Backfill TransactionDetail buying_price
$details = TransactionDetail::all();
foreach ($details as $td) {
    if ($td->buying_price == 0) {
        // Find a stock batch for this product (approximation)
        $stockPrice = ProductStock::where('product_id', $td->product_id)->value('unit_price');
        if ($stockPrice) {
            $td->update(['buying_price' => $stockPrice]);
            echo "Updated TransactionDetail ID {$td->id} with buying_price {$stockPrice}\n";
        }
    }
}

echo "Backfill completed.\n";
