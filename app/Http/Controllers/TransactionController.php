<?php

namespace App\Http\Controllers;

use App\Models\ProductStock;
use App\Models\Transaction;
use Carbon\Carbon;
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
        $request->validate([
            'items' => 'required|array',
            'totalQty' => 'required|numeric',
            'totalAmount' => 'required|numeric',
            'created_at' => 'nullable|date',
            'offline_uuid' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:Cash,Kredit,QRIS,Transfer',
            'is_paid' => 'required|boolean',
        ]);

        if ($request->filled('offline_uuid')) {
            $existingTransaction = Transaction::where('offline_uuid', $request->offline_uuid)->first();
            if ($existingTransaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi dengan ID offline ini sudah disimpan sebelumnya.',
                    'transaction_id' => $existingTransaction->id,
                ]);
            }
        }

        if ($request->filled('created_at')) {
            $date = Carbon::parse($request->created_at);

            $transactionDate = $date->setTimezone(config('app.timezone'));
        } else {
            $transactionDate = Carbon::now();
        }

        $items = $request->items;
        $totalQty = $request->totalQty;
        $totalAmount = $request->totalAmount;
        $discount = $request->discount ?? 0;
        $transactionDate = $request->created_at ?? Carbon::now('Asia/Jakarta');

        try {
            DB::beginTransaction();

            $transaction = Transaction::create([
                'total_quantity' => $totalQty,
                'total_price' => $totalAmount,
                'discount' => $discount,
                'payment_method' => $request->payment_method,
                'is_paid' => $request->is_paid,
                'created_at' => $transactionDate,
                'updated_at' => $transactionDate,
                'offline_uuid' => $request->offline_uuid,
            ]);

            foreach ($items as $item) {
                $transaction->transactionDetails()->create([
                    'product_id' => $item['id'],
                    'product_price' => $item['price'],
                    'quantity' => $item['qty'],
                    'total_price' => $item['price'] * $item['qty'],
                    'created_at' => $transactionDate,
                    'updated_at' => $transactionDate,
                ]);

                // Update Stok
                $productStock = ProductStock::where('product_id', $item['id'])->whereNull('deleted_at')->where('stock_opname', '>', 0)->orderBy('created_at', 'asc')->first();

                if ($productStock) {
                    $productStock->decrement('stock_opname', $item['qty']);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Transaction Error: ' . $e->getMessage());

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
        $transaction = Transaction::with(['transactionDetails.product'])->findOrFail($id);

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
                'address' => 'Jl. Trans Sulawesi, Motolohu, Kec. Randangan, ' . PHP_EOL . 'Kab. Pohuwato, Gorontalo 96469',
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
    public function updateStatus(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $request->validate([
            'is_paid' => 'required|boolean',
        ]);

        $transaction->update([
            'is_paid' => $request->is_paid,
        ]);

        return redirect()->back()->with('success', 'Status pembayaran berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //
    }
}
