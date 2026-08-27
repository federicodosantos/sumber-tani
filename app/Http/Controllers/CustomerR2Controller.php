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
use App\Services\DecimalMathService;
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

        $query = Customer::query()
            ->withSum(['invoices as total_debt' => function ($q) {
                $q->where('type', Invoice::TYPE_PURCHASE);
            }], 'debts')
            ->ofType($type === 'all' ? null : $type);

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
            ->with([
                'transaction.transactionDetails.product',
                'debtPayment.details.invoice',
                'debtPaymentDetails.debtPayment.paymentInvoice',
            ])
            ->orderBy('created_at', 'desc');

        if ($request->filled('type') && in_array($request->type, [Invoice::TYPE_PURCHASE, Invoice::TYPE_DEBT_PAYMENT])) {
            $invoicesQuery->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $invoicesQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $invoicesQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $invoices = $invoicesQuery->paginate(15, ['*'], 'invoices_page')->withQueryString();

        $debtPayments = $customer->debtPayments()
            ->with(['details.invoice', 'paymentInvoice'])
            ->orderBy('payment_date', 'desc')
            ->paginate(5, ['*'], 'payments_page')
            ->withQueryString();

        $creditBalance = (float) $customer->credit_balance;

        return view('customer-r2.show', compact('customer', 'totalDebt', 'invoices', 'debtPayments', 'creditBalance'));
    }

    /**
     * Show the debt payment form for a customer.
     */
    public function payDebt(Customer $customer)
    {
        $totalDebt = $customer->invoices()->where('debts', '>', 0)->sum('debts');
        $creditBalance = (float) $customer->credit_balance;

        return view('customer-r2.payment', compact('customer', 'totalDebt', 'creditBalance'));
    }

    /**
     * Process debt payment using FIFO logic and record audit trail.
     */
    public function processPayment(Request $request, Customer $customer)
    {
        $this->normalizeDecimalInput($request);

        $validator = Validator::make($request->all(), [
            'amount'             => 'required|numeric|min:0|decimal:0,3',
            'payment_method'     => 'required|string|in:Cash,Transfer,QRIS',
            'payment_date'       => 'required|date|before_or_equal:now',
            'use_credit_amount'  => 'nullable|numeric|min:0|decimal:0,3',
            'overpayment_action' => 'nullable|string|in:credit,refund',
        ], [
            'amount.required'         => 'Nominal pembayaran wajib diisi.',
            'amount.numeric'          => 'Nominal pembayaran harus berupa angka.',
            'amount.min'              => 'Nominal pembayaran tidak boleh negatif.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in'       => 'Metode pembayaran tidak valid.',
            'payment_date.required'   => 'Tanggal pembayaran wajib diisi.',
            'payment_date.date'       => 'Format tanggal pembayaran tidak valid.',
            'payment_date.before_or_equal' => 'Tanggal pembayaran tidak boleh di masa depan.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator)->with('open_modal', 'pay-debt');
        }

        $validated      = $validator->validated();
        $math           = app(DecimalMathService::class);
        $cashAmount     = $math->round($validated['amount']);
        $creditUsed     = $math->round($validated['use_credit_amount'] ?? 0);
        $paymentMethod  = $validated['payment_method'];
        $paymentDate    = $this->dateWithCurrentTime($validated['payment_date']);
        $overpayAction  = $validated['overpayment_action'] ?? null;

        // Validate: at least some payment must happen
        if ($math->isZero($cashAmount) && $math->isZero($creditUsed)) {
            return redirect()->back()->withInput()
                ->withErrors(['amount' => 'Minimal ada nominal tunai atau saldo kredit yang digunakan.'])
                ->with('open_modal', 'pay-debt');
        }

        // Validate: credit used cannot exceed available balance
        $availableCredit = $math->round((string) $customer->credit_balance);
        if ($math->compare($creditUsed, $availableCredit) > 0) {
            return redirect()->back()->withInput()
                ->withErrors(['amount' => 'Saldo kredit yang digunakan (Rp ' . number_format($creditUsed, 0, ',', '.') . ') melebihi sisa saldo tersedia (Rp ' . number_format($availableCredit, 0, ',', '.') . ').'])
                ->with('open_modal', 'pay-debt');
        }

        $totalDebt = $math->round((string) $customer->invoices()
            ->where('type', Invoice::TYPE_PURCHASE)
            ->where('debts', '>', 0)
            ->sum('debts'));

        $effectivePayment = $math->add($cashAmount, $creditUsed);
        $excess = $math->subtract($effectivePayment, $totalDebt);
        if ($math->isNegative($excess)) {
            $excess = '0.000';
        }

        // If there is overpayment, an action must be chosen
        if ($math->isPositive($excess) && ! in_array($overpayAction, ['credit', 'refund'], true)) {
            return redirect()->back()->withInput()
                ->withErrors(['amount' => 'Terdapat kelebihan bayar Rp ' . number_format($excess, 0, ',', '.') . '. Pilih aksi: simpan sebagai saldo atau refund tunai.'])
                ->with('open_modal', 'pay-debt');
        }

        try {
            DB::transaction(function () use ($customer, $math, $cashAmount, $creditUsed, $effectivePayment, $excess, $overpayAction, $paymentMethod, $paymentDate) {

                // Kunci baris customer agar pembacaan/penulisan credit_balance
                // aman dari race pembayaran hutang yang berjalan bersamaan.
                $lockedCustomer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();

                // 1. Create receipt invoice (debt_payment type)
                $paymentInvoice = Invoice::create([
                    'customer_id'    => $customer->id,
                    'transaction_id' => null,
                    'debts'          => 0,
                    'type'           => Invoice::TYPE_DEBT_PAYMENT,
                    'inv_code'       => Invoice::generateInvCode(Invoice::TYPE_DEBT_PAYMENT),
                    'created_at'     => $paymentDate,
                    'updated_at'     => $paymentDate,
                ]);

                // 2. Create debt payment record
                $creditAmount = ($math->isPositive($excess) && $overpayAction === 'credit') ? $excess : '0.000';
                $refundAmount = ($math->isPositive($excess) && $overpayAction === 'refund') ? $excess : '0.000';

                $debtPayment = DebtPayment::create([
                    'customer_id'        => $customer->id,
                    'payment_invoice_id' => $paymentInvoice->id,
                    'amount'             => $cashAmount,
                    'payment_method'     => $paymentMethod,
                    'payment_date'       => $paymentDate,
                    'credit_amount'      => $creditAmount,
                    'refund_amount'      => $refundAmount,
                    'credit_used'        => $creditUsed,
                ]);

                // 3. FIFO: distribute effectivePayment across unpaid invoices (oldest first)
                $creditBalance = (string) $lockedCustomer->credit_balance;
                $remaining = $effectivePayment;
                $invoices  = $lockedCustomer->invoices()
                    ->where('type', Invoice::TYPE_PURCHASE)
                    ->where('debts', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach ($invoices as $invoice) {
                    if ($math->compare($remaining, 0) <= 0) {
                        break;
                    }

                    $debtBefore = (string) $invoice->debts;
                    $paymentForThisInvoice = $math->compare($remaining, $invoice->debts) <= 0
                        ? $remaining
                        : (string) $invoice->debts;

                    $invoice->update(['debts' => $math->subtract((string) $invoice->debts, $paymentForThisInvoice)]);
                    $remaining = $math->subtract($remaining, $paymentForThisInvoice);

                    if ($math->isZero((string) $invoice->fresh()->debts) && $invoice->transaction) {
                        $invoice->transaction->update(['is_paid' => true]);
                    }

                    DebtPaymentDetail::create([
                        'debt_payment_id' => $debtPayment->id,
                        'invoice_id'      => $invoice->id,
                        'amount_paid'     => $paymentForThisInvoice,
                        'debt_before'     => $debtBefore,
                        'debt_after'      => (string) $invoice->fresh()->debts,
                    ]);
                }

                // 4. Handle credit balance changes (used credit + saved overpayment)
                if ($math->isPositive($creditUsed)) {
                    $creditBalance = $math->subtract($creditBalance, $creditUsed);
                }
                if ($math->isPositive($creditAmount)) {
                    $creditBalance = $math->add($creditBalance, $creditAmount);
                }
                $lockedCustomer->update(['credit_balance' => $creditBalance]);
            });

            $msg = 'Pembayaran hutang berhasil diproses.';
            if ($overpayAction === 'credit') {
                $msg .= ' Kelebihan bayar disimpan sebagai saldo kredit.';
            } elseif ($overpayAction === 'refund') {
                $msg .= ' Kelebihan bayar dicatat sebagai refund tunai.';
            }
            if ($math->isPositive($creditUsed)) {
                $msg .= ' Saldo kredit Rp ' . number_format($creditUsed, 0, ',', '.') . ' digunakan.';
            }

            return redirect()->route('customer-r2.show', $customer->id)->with('success', $msg);
        } catch (Exception $e) {
            return redirect()->back()->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan saat memproses pembayaran: ' . $e->getMessage()])
                ->with('open_modal', 'pay-debt');
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
        $this->normalizeDecimalInput($request);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:products,id',
            'items.*.price' => 'required|numeric|min:0|decimal:0,3',
            'items.*.qty' => 'required|numeric|min:0.001|decimal:0,3',
            'items.*.basePrice' => 'nullable|numeric|min:0',
            'totalQty' => 'required|numeric|min:0.001',
            'totalAmount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|decimal:0,3',
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

        $math = app(DecimalMathService::class);

        $isPaid = (bool) $validated['is_paid'];

        $discountRaw = $validated['discount'] ?? 0;
        $discount = $math->round($discountRaw === '' ? 0 : $discountRaw);

        $preparedItems = [];
        $totalQty = '0.000';
        $subtotal = '0.000';

        foreach ($validated['items'] as $item) {
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
        if ($validated['payment_method'] === 'Cash') {
            $cashReceived = isset($validated['cash_received']) ? $math->round($validated['cash_received']) : null;
            $changeAmount = isset($validated['change_amount']) ? $math->round($validated['change_amount']) : null;

            if ($isPaid && $cashReceived !== null && $math->compare($cashReceived, $totalAmount) < 0) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['cash_received' => 'Uang diterima kurang dari total transaksi.']);
            }
        }

        try {
            DB::transaction(function () use ($customer, $preparedItems, $totalAmount, $totalQty, $discount, $isPaid, $cashReceived, $changeAmount, $transactionDate, $validated, $math) {
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

                foreach ($preparedItems as $item) {
                    $transaction->transactionDetails()->create([
                        'product_id' => $item['id'],
                        'product_stock_id' => null,
                        'product_price' => $item['price'],
                        'buying_price' => '0.000',
                        'quantity' => $item['qty'],
                        'total_price' => $item['lineTotal'],
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

                foreach ($preparedItems as $item) {
                    $basePrice = $item['basePrice'];
                    $manualPrice = $item['price'];

                    if ($basePrice !== null && $math->compare($manualPrice, $basePrice) !== 0) {
                        CustomerProductPrice::updateOrCreate(
                            [
                                'customer_id' => $customer->id,
                                'product_id'  => $item['id'],
                            ],
                            [
                                'custom_price' => $manualPrice,
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
        ], [
            'amount.required' => 'Nominal hutang wajib diisi.',
            'amount.numeric' => 'Nominal hutang harus berupa angka.',
            'amount.min' => 'Nominal hutang minimal Rp 1.',
            'note.required' => 'Keterangan wajib diisi.',
            'note.max' => 'Keterangan maksimal 1000 karakter.',
            'created_at.required' => 'Tanggal wajib diisi.',
            'created_at.date' => 'Format tanggal tidak valid.',
            'created_at.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($validator)
                ->with('open_modal', 'add-debt');
        }

        $validated = $validator->validated();
        $debtDate = $this->dateWithCurrentTime($validated['created_at']);

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
     * Combine a user-picked date with the current time so records keep
     * a meaningful timestamp instead of falling to 00:00:00.
     */
    private function dateWithCurrentTime(string $date): Carbon
    {
        $now = Carbon::now(config('app.timezone'));

        return Carbon::parse($date, config('app.timezone'))
            ->setTime($now->hour, $now->minute, $now->second);
    }

    /**
     * Update a manual debt invoice (purchasement type, no transaction).
     * OWNER only. Blocked entirely if any debt_payment_details reference this invoice.
     */
    public function updateDebt(Request $request, Invoice $invoice)
    {
        $this->ownerOnly();

        if ($invoice->type !== Invoice::TYPE_PURCHASE || $invoice->transaction_id !== null) {
            abort(404, 'Bukan hutang manual.');
        }

        $alreadyPaid = (float) DB::table('debt_payment_details')
            ->where('invoice_id', $invoice->id)
            ->sum('amount_paid');

        if ($alreadyPaid > 0) {
            return redirect()->back()
                ->withErrors([
                    'general' => 'Hutang ini sudah memiliki pembayaran terkait sebesar Rp '
                        . number_format($alreadyPaid, 0, ',', '.')
                        . '. Hapus pembayaran tersebut dulu sebelum mengedit hutang.',
                ])
                ->with('open_modal', 'edit-debt-' . $invoice->id);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'note' => 'required|string|max:1000',
            'created_at' => 'required|date|before_or_equal:now',
        ], [
            'amount.required' => 'Nominal hutang wajib diisi.',
            'amount.numeric' => 'Nominal hutang harus berupa angka.',
            'amount.min' => 'Nominal hutang minimal Rp 1.',
            'note.required' => 'Keterangan wajib diisi.',
            'note.max' => 'Keterangan maksimal 1000 karakter.',
            'created_at.required' => 'Tanggal wajib diisi.',
            'created_at.date' => 'Format tanggal tidak valid.',
            'created_at.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator)
                ->with('open_modal', 'edit-debt-' . $invoice->id);
        }

        $validated = $validator->validated();

        try {
            $debtDate = $this->dateWithCurrentTime($validated['created_at']);
            $invoice->update([
                'debts' => (float) $validated['amount'],
                'note' => $validated['note'],
                'created_at' => $debtDate,
                'updated_at' => $debtDate,
            ]);

            return redirect()->route('customer-r2.show', $invoice->customer_id)
                ->with('success', 'Hutang manual berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()
                ->withErrors(['general' => 'Gagal memperbarui hutang: ' . $e->getMessage()])
                ->with('open_modal', 'edit-debt-' . $invoice->id);
        }
    }

    /**
     * Delete a manual debt invoice. Blocked if it has any payment allocations.
     * OWNER only.
     */
    public function destroyDebt(Invoice $invoice)
    {
        $this->ownerOnly();

        if ($invoice->type !== Invoice::TYPE_PURCHASE || $invoice->transaction_id !== null) {
            abort(404, 'Bukan hutang manual.');
        }

        $alreadyPaid = (float) DB::table('debt_payment_details')
            ->where('invoice_id', $invoice->id)
            ->sum('amount_paid');

        if ($alreadyPaid > 0) {
            return redirect()->back()->withErrors([
                'general' => 'Hutang ini sudah dibayar sebagian (Rp ' . number_format($alreadyPaid, 0, ',', '.') . '). Hapus pembayaran terkait dulu sebelum menghapus hutang.',
            ]);
        }

        $customerId = $invoice->customer_id;

        try {
            $invoice->delete();
            return redirect()->route('customer-r2.show', $customerId)
                ->with('success', 'Hutang manual berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['general' => 'Gagal menghapus hutang: ' . $e->getMessage()]);
        }
    }

    /**
     * Update a debt payment (PAY...) by rolling back details and re-FIFO distributing.
     * OWNER only.
     */
    public function updateDebtPayment(Request $request, Invoice $invoice)
    {
        $this->ownerOnly();

        if ($invoice->type !== Invoice::TYPE_DEBT_PAYMENT) {
            abort(404, 'Bukan invoice pembayaran hutang.');
        }

        $this->normalizeDecimalInput($request);

        // Allow caller to override which modal re-opens on validation error
        // (e.g., when editing from inside an edit-debt modal across pagination).
        $errorModalKey = (string) $request->input('open_modal_on_error', 'edit-payment-' . $invoice->id);

        $debtPayment = $invoice->debtPayment;
        if (! $debtPayment) {
            return redirect()->back()->withErrors(['general' => 'Data pembayaran tidak ditemukan.']);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.001|decimal:0,3',
            'payment_method' => 'required|string|in:Cash,Transfer,QRIS',
            'created_at' => 'required|date|before_or_equal:now',
        ], [
            'amount.required' => 'Nominal pembayaran wajib diisi.',
            'amount.numeric' => 'Nominal pembayaran harus berupa angka.',
            'amount.min' => 'Nominal pembayaran minimal Rp 0,001.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
            'created_at.required' => 'Tanggal wajib diisi.',
            'created_at.date' => 'Format tanggal tidak valid.',
            'created_at.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator)
                ->with('open_modal', $errorModalKey);
        }

        $validated = $validator->validated();
        $math = app(DecimalMathService::class);

        $newAmount = $math->round($validated['amount']);
        $oldCreditUsed = $math->round((string) $debtPayment->credit_used);
        $oldCreditAmount = $math->round((string) $debtPayment->credit_amount);
        $rollbackDebt = $math->round((string) $debtPayment->details()->sum('amount_paid'));

        // Server-side upper limit: the new amount cannot exceed the total debt
        // that will be available after rolling back this payment's allocations.
        $customer = $invoice->customer;
        $currentTotalDebt = $math->round((string) $customer->invoices()
            ->where('type', Invoice::TYPE_PURCHASE)
            ->where('debts', '>', 0)
            ->sum('debts'));

        $maxAllowed = $math->add($currentTotalDebt, $rollbackDebt);

        if ($math->compare($newAmount, $maxAllowed) > 0) {
            return redirect()->back()->withInput()
                ->withErrors(['general' => 'Nominal pembayaran baru (Rp ' . number_format($newAmount, 0, ',', '.') . ') melebihi total hutang tersedia (Rp ' . number_format($maxAllowed, 0, ',', '.') . ') setelah rollback.'])
                ->with('open_modal', $errorModalKey);
        }

        try {
            DB::transaction(function () use ($validated, $invoice, $debtPayment, $math, $oldCreditUsed, $oldCreditAmount, $newAmount) {
                $customer = $invoice->customer;

                // Step 1: rollback credit balance changes from the old payment
                // Reverse: credit that was given out → take it back
                //          credit that was consumed → restore it
                $creditBalance = $math->round((string) $customer->credit_balance);
                if ($math->isPositive($oldCreditAmount)) {
                    $creditBalance = $math->subtract($creditBalance, $oldCreditAmount);
                }
                if ($math->isPositive($oldCreditUsed)) {
                    $creditBalance = $math->add($creditBalance, $oldCreditUsed);
                }
                $customer->update(['credit_balance' => $creditBalance]);

                // Step 2: rollback all details (restore debts on source invoices)
                foreach ($debtPayment->details()->get() as $detail) {
                    $sourceInvoice = $detail->invoice()->first();
                    if ($sourceInvoice) {
                        $sourceInvoice->update(['debts' => $math->add((string) $sourceInvoice->debts, (string) $detail->amount_paid)]);
                        if ($sourceInvoice->transaction) {
                            $sourceInvoice->transaction->update(['is_paid' => false]);
                        }
                    }
                    $detail->delete();
                }

                // Step 3: re-FIFO (no credit usage on edit — keep it simple)
                $remaining = $newAmount;
                $sourceInvoices = $customer->invoices()
                    ->where('type', Invoice::TYPE_PURCHASE)
                    ->where('debts', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach ($sourceInvoices as $src) {
                    if ($math->compare($remaining, 0) <= 0) {
                        break;
                    }
                    $debtBefore = (string) $src->debts;
                    $payForThis = $math->compare($remaining, $src->debts) <= 0
                        ? $remaining
                        : (string) $src->debts;

                    $src->update(['debts' => $math->subtract((string) $src->debts, $payForThis)]);
                    $remaining = $math->subtract($remaining, $payForThis);

                    if ($math->isZero((string) $src->fresh()->debts) && $src->transaction) {
                        $src->transaction->update(['is_paid' => true]);
                    }

                    DebtPaymentDetail::create([
                        'debt_payment_id' => $debtPayment->id,
                        'invoice_id'      => $src->id,
                        'amount_paid'     => $payForThis,
                        'debt_before'     => $debtBefore,
                        'debt_after'      => (string) $src->fresh()->debts,
                    ]);
                }

                // Step 4: update parent records
                // On edit, overpayment/credit-use are reset to 0 for simplicity
                $newDate = $this->dateWithCurrentTime($validated['created_at']);
                $debtPayment->update([
                    'amount'         => $newAmount,
                    'payment_method' => $validated['payment_method'],
                    'payment_date'   => $newDate,
                    'credit_amount'  => '0.000',
                    'refund_amount'  => '0.000',
                    'credit_used'    => '0.000',
                ]);
                $invoice->update([
                    'created_at' => $newDate,
                    'updated_at' => $newDate,
                ]);
            });

            return redirect()->route('customer-r2.show', $invoice->customer_id)
                ->with('success', 'Pembayaran hutang berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()
                ->withErrors(['general' => 'Gagal memperbarui pembayaran: ' . $e->getMessage()])
                ->with('open_modal', $errorModalKey);
        }
    }

    /**
     * Delete a debt payment, restoring debts on each source invoice.
     * OWNER only.
     */
    public function destroyDebtPayment(Invoice $invoice)
    {
        $this->ownerOnly();

        if ($invoice->type !== Invoice::TYPE_DEBT_PAYMENT) {
            abort(404, 'Bukan invoice pembayaran hutang.');
        }

        $customerId = $invoice->customer_id;
        $math = app(DecimalMathService::class);

        try {
            DB::transaction(function () use ($invoice, $math) {
                $debtPayment = $invoice->debtPayment;

                if ($debtPayment) {
                    $customer = $invoice->customer;
                    $creditBalance = $math->round((string) $customer->credit_balance);

                    $creditAmount = $math->round((string) $debtPayment->credit_amount);
                    $creditUsed = $math->round((string) $debtPayment->credit_used);

                    // Reverse credit_balance changes from this payment:
                    // credit that was saved → remove it back
                    if ($math->isPositive($creditAmount)) {
                        $creditBalance = $math->subtract($creditBalance, $creditAmount);
                    }
                    // credit that was consumed → restore it
                    if ($math->isPositive($creditUsed)) {
                        $creditBalance = $math->add($creditBalance, $creditUsed);
                    }
                    $customer->update(['credit_balance' => $creditBalance]);

                    foreach ($debtPayment->details()->get() as $detail) {
                        $sourceInvoice = $detail->invoice()->first();
                        if ($sourceInvoice) {
                            $sourceInvoice->update(['debts' => $math->add((string) $sourceInvoice->debts, (string) $detail->amount_paid)]);
                            if ($sourceInvoice->transaction) {
                                $sourceInvoice->transaction->update(['is_paid' => false]);
                            }
                        }
                        $detail->delete();
                    }
                    $debtPayment->delete();
                }

                $invoice->delete();
            });

            return redirect()->route('customer-r2.show', $customerId)
                ->with('success', 'Pembayaran hutang berhasil dibatalkan dan hutang dipulihkan.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['general' => 'Gagal membatalkan pembayaran: ' . $e->getMessage()]);
        }
    }

    /**
     * Soft-delete a customer.
     * Blocked if the customer still has any unpaid invoices (debts > 0).
     * Historical invoices/transactions are preserved in the database.
     */
    public function destroy(Customer $customer)
    {
        // Guard: block deletion if any active debt exists
        $hasActiveDebt = $customer->invoices()
            ->where('type', Invoice::TYPE_PURCHASE)
            ->where('debts', '>', 0)
            ->exists();

        if ($hasActiveDebt) {
            return redirect()
                ->back()
                ->withErrors([
                    'general' => 'Pelanggan tidak dapat dihapus karena masih memiliki hutang aktif. Lunasi semua hutang terlebih dahulu.',
                ]);
        }

        try {
            $label = strtoupper($customer->type);
            $name  = $customer->name;
            $customer->delete(); // soft delete — records in invoices/transactions are preserved

            return redirect()
                ->route('customer-r2.index')
                ->with('success', "Pelanggan {$label} \"{$name}\" berhasil dihapus.");
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['general' => 'Gagal menghapus pelanggan: ' . $e->getMessage()]);
        }
    }

    private function ownerOnly(): void
    {
        if (! auth()->check() || ! auth()->user()->isOwner()) {
            abort(403, 'Hanya OWNER yang dapat melakukan aksi ini.');
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
                    'qty' => (float) $detail->quantity,
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

    private function normalizeDecimalInput(Request $request): void
    {
        $data = $request->all();

        foreach (['amount', 'use_credit_amount', 'discount', 'totalQty', 'totalAmount', 'cash_received', 'change_amount'] as $key) {
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
