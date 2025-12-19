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
        // 1. TAMBAHAN: Validasi input agar aman
        $request->validate([
            'items' => 'required|array',
            'totalQty' => 'required|numeric',
            'totalAmount' => 'required|numeric',
            'created_at' => 'nullable|date', // Ini field kunci untuk offline mode
            'offline_uuid' => 'nullable|string',
        ]);

        if ($request->filled('offline_uuid')) {
            // Cek apakah transaksi dengan offline_uuid ini sudah ada
            $existingTransaction = Transaction::where('offline_uuid', $request->offline_uuid)->first();
            if ($existingTransaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi dengan ID offline ini sudah disimpan sebelumnya.',
                    'transaction_id' => $existingTransaction->id,
                ]); // Conflict
            }
        }

        if ($request->filled('created_at')) {
            // 1. Parse tanggal dari JS (yang formatnya UTC / Zulu Time 'Z')
            // Carbon otomatis tahu ini UTC karena ada huruf 'Z' di belakang string ISO
            $date = Carbon::parse($request->created_at);

            // 2. Ubah ke Timezone Aplikasi (Asia/Jakarta / WIB)
            $transactionDate = $date->setTimezone(config('app.timezone'));
        } else {
            // Jika tidak ada kiriman (Online biasa), pakai waktu server sekarang
            $transactionDate = Carbon::now();
        }

        $items = $request->items;
        $totalQty = $request->totalQty;
        $totalAmount = $request->totalAmount;

        // Ambil waktu dari request (jika sync offline) atau pakai waktu sekarang (jika online biasa)
        $transactionDate = $request->created_at ?? Carbon::now('Asia/Jakarta');

        try {
            DB::beginTransaction();

            // 2. MODIFIKASI: Masukkan created_at secara manual
            $transaction = Transaction::create([
                'total_quantity' => $totalQty,
                'total_price' => $totalAmount,
                'created_at' => $transactionDate, // Override timestamp
                'updated_at' => $transactionDate, // Samakan saja
                'offline_uuid' => $request->offline_uuid,
            ]);

            foreach ($items as $item) {
                $transaction->transactionDetails()->create([
                    'product_id' => $item['id'],
                    'product_price' => $item['price'], // Cek apakah key di JS 'price' atau 'product_price'
                    'quantity' => $item['qty'],
                    'total_price' => $item['price'] * $item['qty'],
                    'created_at' => $transactionDate, // Detail juga harus ikut waktu asli
                    'updated_at' => $transactionDate,
                ]);

                // Update Stok
                $productStock = ProductStock::where('product_id', $item['id'])
                    ->whereNull('deleted_at')
                    ->where('stock_opname', '>', 0) // Tambahan: Hanya ambil yang stoknya ada
                    ->orderBy('created_at', 'asc') // Tambahan: Ambil stok terlama dulu (FIFO)
                    ->first();

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
