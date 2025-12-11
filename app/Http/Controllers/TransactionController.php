<?php

namespace App\Http\Controllers;

use App\Models\ProductStock;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            DB::beginTransaction();

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

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
                'totalQty' => $totalQty,
                'totalAmount' => $totalAmount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

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
    public function show($id)
    {
        // Load transaction + details + product
        $transaction = Transaction::with(['transactionDetails.product'])->findOrFail($id);

        // Map details into clean JSON objects
        $items = $transaction->transactionDetails->map(function ($detail) {
            return [
                'name' => $detail->product?->name ?? 'Unknown',
                'price' => (float) $detail->product_price,
                'qty' => (int) $detail->quantity,
                'total' => (float) $detail->total_price,
            ];
        });

        return response()->json([
            'store' => [
                'name' => 'TOKO SUMBERTANI',
                'address' => 'Jl. Trans Sulawesi, Motolohu, Kec. Randangan, '.PHP_EOL.'Kab. Pohuwato, Gorontalo 96469',
                'phone' => '+6282293913193',
                'email' => 'admin@sumbertani.net',
            ],

            'transaction' => [
                'id' => $transaction->id,
                'datetime' => $transaction->created_at->translatedFormat('d M Y H:i'),
                'total_qty' => (int) $transaction->total_quantity,
                'total' => (float) $transaction->total_price,
            ],

            'items' => $items,
        ]);
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
