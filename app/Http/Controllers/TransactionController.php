<?php

namespace App\Http\Controllers;

use App\Models\CustomerProductPrice;
use App\Models\Invoice;
use App\Models\ProductStock;
use App\Models\Transaction;
use App\Services\DecimalMathService;
use App\Services\ProductStockService;
use Carbon\Carbon;
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
        $this->normalizeDecimalInput($request);

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:products,id',
            'items.*.price' => 'required|numeric|min:0|decimal:0,3',
            'items.*.qty' => 'required|numeric|min:0.001|decimal:0,3',
            'totalQty' => 'required|numeric',
            'totalAmount' => 'required|numeric',
            'created_at' => 'nullable|date',
            'offline_uuid' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0|decimal:0,3',
            'payment_method' => 'required|string|in:Cash,Kredit,QRIS,Transfer',
            'is_paid' => 'required|boolean',
            'cash_received' => 'nullable|numeric|min:0',
            'change_amount' => 'nullable|numeric',
            'customer_id' => 'nullable|integer|exists:customers,id',
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

        $math = app(DecimalMathService::class);

        $discount = $math->round($request->input('discount', 0));

        $preparedItems = [];
        $totalQty = '0.000';
        $subtotal = '0.000';

        foreach ($request->input('items') as $item) {
            $id = (int) $item['id'];
            $price = $math->round($item['price']);
            $qty = $math->round($item['qty']);
            $lineTotal = $math->multiply($price, $qty);

            $totalQty = $math->add($totalQty, $qty);
            $subtotal = $math->add($subtotal, $lineTotal);

            $preparedItems[] = [
                'id' => $id,
                'price' => $price,
                'qty' => $qty,
                'basePrice' => $item['basePrice'] ?? null,
                'lineTotal' => $lineTotal,
            ];
        }

        $totalAmount = $math->subtract($subtotal, $discount);
        if ($math->isNegative($totalAmount)) {
            $totalAmount = '0.000';
        }

        $cashReceived = null;
        $changeAmount = null;
        if ($request->payment_method === 'Cash') {
            $cashReceived = $request->filled('cash_received') ? $math->round($request->input('cash_received')) : null;
            $changeAmount = $request->filled('change_amount') ? $math->round($request->input('change_amount')) : null;

            if ($request->is_paid && $cashReceived !== null && $math->compare($cashReceived, $totalAmount) < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Uang diterima kurang dari total transaksi.',
                ], 422);
            }
        }

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
                'transaction_date' => $transactionDate,
                'offline_uuid' => $request->offline_uuid,
                'cash_received' => $cashReceived,
                'change_amount' => $changeAmount,
            ]);

            foreach ($preparedItems as $item) {
                $stockService = app(ProductStockService::class);
                $allocations = $stockService->allocateStockFifo($item['id'], $item['qty']);

                foreach ($allocations as $allocation) {
                    $transaction->transactionDetails()->create([
                        'product_id' => $item['id'],
                        'product_stock_id' => $allocation['stock_id'],
                        'product_price' => $item['price'],
                        'buying_price' => $allocation['unit_price'],
                        'quantity' => $allocation['quantity'],
                        'total_price' => $math->multiply($item['price'], $allocation['quantity']),
                        'created_at' => $transactionDate,
                        'updated_at' => $transactionDate,
                    ]);
                }
            }

            // If R2 customer is attached, create invoice automatically
            if ($request->filled('customer_id')) {
                $debtAmount = $request->payment_method === 'Kredit' ? $totalAmount : 0;

                Invoice::create([
                    'customer_id' => $request->customer_id,
                    'transaction_id' => $transaction->id,
                    'debts' => $debtAmount,
                    'type' => Invoice::TYPE_PURCHASE,
                    'inv_code' => Invoice::generateInvCode(Invoice::TYPE_PURCHASE),
                ]);

                foreach ($preparedItems as $item) {
                    $basePrice = $item['basePrice'];
                    $manualPrice = $item['price'];

                    if ($basePrice !== null && $math->compare($manualPrice, $basePrice) !== 0) {
                        CustomerProductPrice::updateOrCreate(
                            [
                                'customer_id' => $request->customer_id,
                                'product_id' => $item['id'],
                            ],
                            [
                                'custom_price' => $manualPrice,
                            ]
                        );
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
            ]);
        } catch (\RuntimeException $e) {
            DB::rollBack();

            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                422,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Transaction Error: '.$e->getMessage());

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
                'price' => round((float) $detail->product_price, 3),
                'qty' => round((float) $detail->quantity, 3),
                'total' => round((float) $detail->total_price, 3),
            ];
        });

        return response()->json([
            'store' => [
                'name' => 'TOKO SUMBERTANI',
                'address' => 'Jl. Trans Sulawesi, Motolohu, Kec. Randangan, '.PHP_EOL.'Kab. Pohuwato, Gorontalo 96469',
                'phone' => '+6281356745129',
                'email' => 'sumbertani0209@gmail.com',
            ],

            'transaction' => [
                'id' => $transaction->id,
                'datetime' => $transaction->created_at->translatedFormat('d M Y H:i'),
                'total_qty' => round((float) $transaction->total_quantity, 3),
                'discount' => round((float) $transaction->discount, 3),
                'total' => round((float) $transaction->total_price, 3),
                'payment_method' => $transaction->payment_method,
                'cash_received' => $transaction->cash_received !== null ? round((float) $transaction->cash_received, 3) : null,
                'change_amount' => $transaction->change_amount !== null ? round((float) $transaction->change_amount, 3) : null,
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

        // If transaction is marked as paid, also clear any related invoice debts
        if ($request->is_paid) {
            DB::table('invoices')->where('transaction_id', $transaction->id)->update(['debts' => 0]);
        }

        return redirect()->back()->with('success', 'Status pembayaran berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //
    }

    private function normalizeDecimalInput(Request $request): void
    {
        $data = $request->all();

        foreach (['discount', 'totalQty', 'totalAmount', 'cash_received', 'change_amount'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->normalizeDecimal($data[$key]);
            }
        }

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }

                foreach (['price', 'qty', 'basePrice'] as $field) {
                    if (array_key_exists($field, $item)) {
                        $data['items'][$i][$field] = $this->normalizeDecimal($item[$field]);
                    }
                }
            }
        }

        $request->merge($data);
    }

    private function normalizeDecimal(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return str_replace(',', '.', trim($value));
    }
}
