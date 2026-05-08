<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DebtPayment;
use App\Models\DebtPaymentDetail;
use App\Models\CustomerProductPrice;
use App\Models\Invoice;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class CustomerR2Controller extends Controller
{
    /**
     * Display a listing of R-2 customers.
     */
    public function index(Request $request)
    {
        $type = $request->input('type', 'all');
        $type = in_array($type, ['r1', 'r2'], true) ? $type : 'all';

        $query = Customer::query()->ofType($type === 'all' ? null : $type);

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        switch ($request->input('sort')) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'date_new':
                $query->orderBy('created_at', 'desc');
                break;
            case 'date_old':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderBy('name', 'asc');
        }

        $customers = $query->paginate(10)->withQueryString();

        return view('customer-r2.index', compact('customers', 'type'));
    }

    /**
     * Show the form for creating a new R-2 customer.
     */
    public function create()
    {
        return view('customer-r2.create');
    }

    /**
     * Store a newly created R-2 customer.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:r1,r2',
            'phone_number' => 'required|string|max:15',
            'address' => 'required|string|max:255',
        ]);

        try {
            Customer::create($validated);

            $label = strtoupper($validated['type']);
            return redirect()->route('customer-r2.index')->with('success', "Pelanggan {$label} berhasil ditambahkan.");
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan saat menambahkan pelanggan.']);
        }
    }

    /**
     * Update an existing customer's profile (name, type, phone, address).
     */
    public function update(Request $request, Customer $customer)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:r1,r2',
            'phone_number' => 'required|string|max:15',
            'address' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput()
                ->with('open_modal', 'edit-customer');
        }

        try {
            $customer->update($validator->validated());

            return redirect()
                ->route('customer-r2.show', $customer->id)
                ->with('success', 'Data pelanggan berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan saat memperbarui pelanggan: ' . $e->getMessage()])
                ->with('open_modal', 'edit-customer');
        }
    }

    /**
     * Display R-2 customer detail with debt info, invoices, and payment history.
     */
    public function show(Request $request, Customer $customer)
    {
        $totalDebt = $customer->invoices()->where('type', Invoice::TYPE_PURCHASE)->sum('debts');

        $invoicesQuery = $customer->invoices()
            ->with(['transaction.transactionDetails.product', 'debtPayment.details.invoice'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('type') && in_array($request->type, [Invoice::TYPE_PURCHASE, Invoice::TYPE_DEBT_PAYMENT])) {
            $invoicesQuery->where('type', $request->type);
        }

        $invoices = $invoicesQuery->paginate(15, ['*'], 'invoices_page');

        $debtPayments = $customer->debtPayments()
            ->with(['details.invoice', 'paymentInvoice'])
            ->orderBy('payment_date', 'desc')
            ->paginate(5, ['*'], 'payments_page');

        return view('customer-r2.show', compact('customer', 'totalDebt', 'invoices', 'debtPayments'));
    }

    /**
     * Show the debt payment form for a customer.
     */
    public function payDebt(Customer $customer)
    {
        $totalDebt = $customer->invoices()->where('debts', '>', 0)->sum('debts');

        return view('customer-r2.payment', compact('customer', 'totalDebt'));
    }

    /**
     * Process debt payment using FIFO logic and record audit trail.
     */
    public function processPayment(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:Cash,Transfer,QRIS',
        ]);

        $amount = (float) $validated['amount'];
        $paymentMethod = $validated['payment_method'];

        // Check total debt
        $totalDebt = $customer->invoices()
            ->where('type', Invoice::TYPE_PURCHASE)
            ->where('debts', '>', 0)
            ->sum('debts');

        if ($amount > $totalDebt) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['amount' => 'Nominal pembayaran melebihi total hutang (Rp ' . number_format($totalDebt, 0, ',', '.') . ').']);
        }

        try {
            DB::transaction(function () use ($customer, $amount, $paymentMethod) {
                $originalAmount = $amount;

                // 1. Create receipt invoice (debt_payment type)
                $invCode = Invoice::generateInvCode(Invoice::TYPE_DEBT_PAYMENT);
                $paymentInvoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'transaction_id' => null,
                    'debts' => 0,
                    'type' => Invoice::TYPE_DEBT_PAYMENT,
                    'inv_code' => $invCode,
                ]);

                // 2. Create debt payment record
                $debtPayment = DebtPayment::create([
                    'customer_id' => $customer->id,
                    'payment_invoice_id' => $paymentInvoice->id,
                    'amount' => $originalAmount,
                    'payment_method' => $paymentMethod,
                    'payment_date' => now(),
                ]);

                // 3. FIFO: get unpaid purchase invoices ordered by oldest first
                $invoices = $customer->invoices()
                    ->where('type', Invoice::TYPE_PURCHASE)
                    ->where('debts', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($invoices as $invoice) {
                    if ($amount <= 0) break;

                    $debtBefore = $invoice->debts;
                    $paymentForThisInvoice = 0;

                    if ($amount >= $invoice->debts) {
                        // Payment covers this invoice fully
                        $paymentForThisInvoice = $invoice->debts;
                        $amount -= $invoice->debts;
                        $invoice->update(['debts' => 0]);

                        // Automatically update related transaction status
                        if ($invoice->transaction) {
                            $invoice->transaction->update(['is_paid' => true]);
                        }
                    } else {
                        // Partial payment
                        $paymentForThisInvoice = $amount;
                        $invoice->update(['debts' => $invoice->debts - $amount]);
                        $amount = 0;
                    }

                    // 4. Create detail record for each source invoice
                    if ($paymentForThisInvoice > 0) {
                        DebtPaymentDetail::create([
                            'debt_payment_id' => $debtPayment->id,
                            'invoice_id' => $invoice->id,
                            'amount_paid' => $paymentForThisInvoice,
                            'debt_before' => $debtBefore,
                            'debt_after' => $invoice->fresh()->debts,
                        ]);
                    }
                }
            });

            return redirect()
                ->route('customer-r2.show', $customer->id)
                ->with('success', 'Pembayaran hutang berhasil diproses.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan saat memproses pembayaran: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for adding a manual invoice (historical purchase) for a customer.
     */
    public function createInvoice(Customer $customer)
    {
        $categories = ItemCategory::all();

        $totalStockSub = DB::table('product_stocks')
            ->select('product_id', DB::raw('SUM(stock_opname) as total_stock'))
            ->whereNull('deleted_at')
            ->groupBy('product_id');

        $latestBatchSub = DB::table('product_stocks as ps1')
            ->select('ps1.product_id', 'ps1.price_consument', 'ps1.price_r1', 'ps1.price_r2')
            ->whereNull('ps1.deleted_at')
            ->whereRaw('ps1.batch = (
                SELECT MAX(ps2.batch)
                FROM product_stocks ps2
                WHERE ps2.product_id = ps1.product_id
                AND ps2.deleted_at IS NULL
            )');

        $products = Product::query()
            ->leftJoinSub($totalStockSub, 'ts', 'ts.product_id', '=', 'products.id')
            ->leftJoinSub($latestBatchSub, 'lb', 'lb.product_id', '=', 'products.id')
            ->join('item_categories as ic', 'products.item_category_id', '=', 'ic.id')
            ->select([
                'products.id', 'products.name', 'products.item_category_id', 'products.description',
                'ic.name as category_name',
                DB::raw('COALESCE(ts.total_stock, 0) as stock_opname'),
                'lb.price_consument', 'lb.price_r1', 'lb.price_r2',
            ])
            ->whereNull('products.deleted_at')
            ->orderBy('products.name', 'asc')
            ->get();

        $customPrices = $customer->customProductPrices()
            ->get(['product_id', 'custom_price'])
            ->pluck('custom_price', 'product_id');

        return view('customer-r2.invoice-create', compact('customer', 'categories', 'products', 'customPrices'));
    }

    /**
     * Store a manual invoice for a customer (does NOT decrement stock).
     */
    public function storeInvoice(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:products,id',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.basePrice' => 'nullable|numeric|min:0',
            'totalQty' => 'required|numeric|min:1',
            'totalAmount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:Cash,Kredit,QRIS,Transfer',
            'is_paid' => 'required|boolean',
            'cash_received' => 'nullable|numeric|min:0',
            'change_amount' => 'nullable|numeric',
            'created_at' => 'required|date|before_or_equal:now',
            'note' => 'nullable|string|max:1000',
        ]);

        $now = Carbon::now();
        $transactionDate = Carbon::parse($validated['created_at'])
            ->setTimezone(config('app.timezone'))
            ->setTime($now->hour, $now->minute, $now->second);
        $items = $validated['items'];
        $totalAmount = (float) $validated['totalAmount'];
        $totalQty = (int) $validated['totalQty'];
        $discount = (float) ($validated['discount'] ?? 0);
        $isPaid = (bool) $validated['is_paid'];

        $cashReceived = null;
        $changeAmount = null;
        if ($validated['payment_method'] === 'Cash') {
            $cashReceived = isset($validated['cash_received']) ? (float) $validated['cash_received'] : null;
            $changeAmount = isset($validated['change_amount']) ? (float) $validated['change_amount'] : null;

            if ($isPaid && $cashReceived !== null && $cashReceived < $totalAmount) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['cash_received' => 'Uang diterima kurang dari total transaksi.']);
            }
        }

        try {
            DB::transaction(function () use ($customer, $items, $totalAmount, $totalQty, $discount, $isPaid, $cashReceived, $changeAmount, $transactionDate, $validated) {
                $transaction = Transaction::create([
                    'total_quantity' => $totalQty,
                    'total_price' => $totalAmount,
                    'discount' => $discount,
                    'payment_method' => $validated['payment_method'],
                    'is_paid' => $isPaid,
                    'created_at' => $transactionDate,
                    'updated_at' => $transactionDate,
                    'transaction_date' => $transactionDate,
                    'cash_received' => $cashReceived,
                    'change_amount' => $changeAmount,
                    'is_manual' => true,
                ]);

                foreach ($items as $item) {
                    $transaction->transactionDetails()->create([
                        'product_id' => $item['id'],
                        'product_stock_id' => null,
                        'product_price' => $item['price'],
                        'buying_price' => 0,
                        'quantity' => $item['qty'],
                        'total_price' => $item['price'] * $item['qty'],
                        'created_at' => $transactionDate,
                        'updated_at' => $transactionDate,
                    ]);
                }

                Invoice::create([
                    'customer_id' => $customer->id,
                    'transaction_id' => $transaction->id,
                    'debts' => $isPaid ? 0 : $totalAmount,
                    'type' => Invoice::TYPE_PURCHASE,
                    'inv_code' => Invoice::generateInvCode(Invoice::TYPE_PURCHASE),
                    'note' => $validated['note'] ?? null,
                    'created_at' => $transactionDate,
                    'updated_at' => $transactionDate,
                ]);

                foreach ($items as $item) {
                    $basePrice = $item['basePrice'] ?? null;
                    $manualPrice = $item['price'] ?? null;

                    if ($basePrice !== null && $manualPrice !== null && (float) $manualPrice !== (float) $basePrice) {
                        CustomerProductPrice::updateOrCreate(
                            [
                                'customer_id' => $customer->id,
                                'product_id'  => $item['id'],
                            ],
                            [
                                'custom_price' => (float) $manualPrice,
                            ]
                        );
                    }
                }
            });

            return redirect()
                ->route('customer-r2.show', $customer->id)
                ->with('success', 'Nota manual berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan saat menyimpan nota: ' . $e->getMessage()]);
        }
    }

    /**
     * Store a direct debt entry (no items, no transaction) for a customer.
     */
    public function storeDebt(Request $request, Customer $customer)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'note' => 'required|string|max:1000',
            'created_at' => 'required|date|before_or_equal:now',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($validator)
                ->with('open_modal', 'add-debt');
        }

        $validated = $validator->validated();
        $debtDate = Carbon::parse($validated['created_at'])->setTimezone(config('app.timezone'));

        try {
            Invoice::create([
                'customer_id' => $customer->id,
                'transaction_id' => null,
                'debts' => (float) $validated['amount'],
                'type' => Invoice::TYPE_PURCHASE,
                'inv_code' => Invoice::generateInvCode(Invoice::TYPE_PURCHASE),
                'note' => $validated['note'],
                'created_at' => $debtDate,
                'updated_at' => $debtDate,
            ]);

            return redirect()
                ->route('customer-r2.show', $customer->id)
                ->with('success', 'Hutang berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan saat menyimpan hutang: ' . $e->getMessage()])
                ->with('open_modal', 'add-debt');
        }
    }

    /**
     * Search R-2 customers (JSON API for cashier modal).
     */
    public function search(Request $request)
    {
        $type = $request->input('type');
        $query = Customer::query()->ofType($type);

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name', 'asc')->limit(50)->get(['id', 'name', 'type', 'phone_number', 'address']);

        return response()->json($customers);
    }

    /**
     * Preview invoice as HTML (replaces PDF download).
     */
    public function previewInvoice(Invoice $invoice)
    {
        $invoice->load(['customer', 'transaction.transactionDetails.product', 'debtPayment.details.invoice']);

        $customer = $invoice->customer;
        $transaction = $invoice->transaction;
        $details = $transaction ? $transaction->transactionDetails : collect();

        return view('customer-r2.invoice-preview', compact('invoice', 'customer', 'transaction', 'details'));
    }

    /**
     * Download invoice as PDF.
     */
    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['customer', 'transaction.transactionDetails.product:id,name', 'debtPayment.details.invoice:id,inv_code']);

        $customer = $invoice->customer;
        $transaction = $invoice->transaction;
        $details = $transaction ? $transaction->transactionDetails : collect();

        $pdf = Pdf::loadView('customer-r2.invoice-pdf', compact('invoice', 'customer', 'transaction', 'details'))
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'helvetica',
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'dpi' => 96,
            ]);

        $filename = 'Invoice-' . str_replace('/', '_', $invoice->inv_code ?? $invoice->id) . '-' . str_replace(' ', '_', $customer->name) . '.pdf';
        return $pdf->download($filename);
    }
    /**
     * Get invoice data for thermal printing (JSON API).
     */
    public function receiptData(Invoice $invoice)
    {
        $invoice->load(['customer', 'transaction.transactionDetails.product', 'debtPayment.details.invoice']);

        $customer = $invoice->customer;
        $transaction = $invoice->transaction;
        
        $data = [
            'store' => [
                'name' => 'TOKO SUMBERTANI',
                'address' => 'Jl. Trans Sulawesi, Motolohu, Kec. Randangan, ' . PHP_EOL . 'Kab. Pohuwato, Gorontalo 96469',
                'phone' => '+6281356745129',
                'email' => 'sumbertani0209@gmail.com',
            ],
            'invoice' => [
                'code' => $invoice->inv_code ?? '#' . $invoice->id,
                'type' => $invoice->type, // purchasement or debt_payment
                'datetime' => $invoice->created_at->translatedFormat('d M Y H:i'),
            ],
            'customer' => [
                'name' => $customer->name,
                'phone' => $customer->phone_number,
                'address' => $customer->address,
            ],
        ];

        if ($invoice->type === Invoice::TYPE_PURCHASE && $transaction) {
            $data['transaction'] = [
                'total' => (float) $transaction->total_price,
                'discount' => (float) $transaction->discount,
                'payment_method' => $transaction->payment_method,
                'cash_received' => $transaction->cash_received,
                'change_amount' => $transaction->change_amount,
            ];

            $data['items'] = $transaction->transactionDetails->map(function ($detail) {
                return [
                    'name' => $detail->product?->name ?? 'Unknown',
                    'price' => (float) $detail->product_price,
                    'qty' => (int) $detail->quantity,
                    'total' => (float) $detail->total_price,
                ];
            });
        } elseif ($invoice->type === Invoice::TYPE_DEBT_PAYMENT && $invoice->debtPayment) {
            $debtPayment = $invoice->debtPayment;
            
            $data['payment'] = [
                'total' => (float) $debtPayment->amount,
                'payment_method' => $debtPayment->payment_method,
                'payment_date' => $debtPayment->payment_date->translatedFormat('d M Y H:i'),
            ];

            $data['details'] = $debtPayment->details->map(function ($detail) {
                return [
                    'inv_code' => $detail->invoice->inv_code ?? '#' . $detail->invoice_id,
                    'debt_before' => (float) $detail->debt_before,
                    'amount_paid' => (float) $detail->amount_paid,
                    'debt_after' => (float) $detail->debt_after,
                ];
            });
        }

        return response()->json($data);
    }

    /**
     * Get all custom product prices for a given customer (JSON API).
     */
    public function getCustomPrices(Customer $customer)
    {
        $prices = $customer->customProductPrices()
            ->get(['product_id', 'custom_price'])
            ->pluck('custom_price', 'product_id');

        return response()->json($prices);
    }
}
