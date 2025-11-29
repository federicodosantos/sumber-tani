<?php

namespace App\Http\Controllers;

use App\Models\ProductStock;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        $items = $request->items;
        $totalQty = $request->totalQty;
        $totalAmount = $request->totalAmount;

        try {
            \DB::beginTransaction();

            $transaction = Transaction::create([
                'total_quantity' => $totalQty,
                'total_price' => $totalAmount,
            ]);

            foreach ($items as $item) {
                $transaction->transactionDetails()->create([
                    'product_id' => $item['id'],
                    'product_price' => $item['price'],
                    'quantity' => $item['qty'],
                    'total_price' => $item['price'] * $item['qty'],
                ]);

                $productStock = ProductStock::where('product_id', $item['id'])->whereNull('deleted_at')->first();

                if ($productStock) {
                    $productStock->decrement('stock_opname', $item['qty']);
                }
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'transaction' => $transaction->id,
                'totalQty' => $totalQty,
                'totalAmount' => $totalAmount,
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //
    }
}
