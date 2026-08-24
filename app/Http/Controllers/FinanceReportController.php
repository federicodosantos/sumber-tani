<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerProductPrice;
use App\Models\Invoice;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\ProductStock;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\DecimalMathService;
use App\Services\TransactionReversalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    /**
     * Resolve start & end dates from the range_filter parameter.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string} [startDate, endDate, rangeKey]
     */
    private function resolveDateRange(Request $request): array
    {
        $now = Carbon::now();
        $rangeKey = $request->input('range_filter', 'this_month');

        switch ($rangeKey) {
            case 'this_week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;

            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
                break;

            case 'this_quarter':
                $start = $now->copy()->firstOfQuarter();
                $end = $now->copy()->lastOfQuarter()->endOfDay();
                break;

            case 'custom':
                $start = $request->filled('start_date')
                    ? Carbon::parse($request->input('start_date'))->startOfDay()
                    : $now->copy()->startOfMonth();
                $end = $request->filled('end_date')
                    ? Carbon::parse($request->input('end_date'))->endOfDay()
                    : $now->copy()->endOfMonth();
                break;

            case 'this_month':
            default:
                $rangeKey = 'this_month';
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
        }

        return [$start, $end, $rangeKey];
    }

    /**
     * Build query params for finance.index that will include the given date in the listing.
     */
    private function buildRangeFilterFor(Carbon $date): array
    {
        $now = Carbon::now();

        if ($date->isSameMonth($now)) {
            return ['range_filter' => 'this_month'];
        }

        if ($date->isSameMonth($now->copy()->subMonth())) {
            return ['range_filter' => 'last_month'];
        }

        return [
            'range_filter' => 'custom',
            'start_date' => $date->copy()->startOfDay()->toDateString(),
            'end_date' => $date->copy()->endOfDay()->toDateString(),
        ];
    }

    /**
     * Human-readable label for the chosen range.
     */
    private function getRangeLabel(string $key, Carbon $start, Carbon $end): string
    {
        return match ($key) {
            'this_week' => 'Minggu Ini',
            'this_month' => 'Bulan Ini',
            'last_month' => 'Bulan Lalu',
            'this_quarter' => 'Kuartal Ini',
            'custom' => $start->format('d M Y').' - '.$end->format('d M Y'),
            default => 'Bulan Ini',
        };
    }

    /* ================================================================
       INDEX
    ================================================================ */
    public function index(Request $request)
    {
        [$startDate, $endDate, $rangeKey] = $this->resolveDateRange($request);

        $transactionFilter = $request->input('transaction_filter', 'daily');

        $stats = $this->getStats($startDate, $endDate);
        $chartData = $this->getChartData($startDate, $endDate);
        $financeReports = $this->getFinanceReports($startDate, $endDate);
        $products = $this->getAllProduct();
        $categories = $this->getAllCategories();

        $rangeLabel = $this->getRangeLabel($rangeKey, $startDate, $endDate);

        $profitLoss = $this->calculateProfitLoss($startDate, $endDate);
        $balanceSheet = $this->calculateBalanceSheet($endDate);

        return view('finance.index', compact(
            'stats',
            'chartData',
            'financeReports',
            'transactionFilter',
            'products',
            'categories',
            'rangeKey',
            'rangeLabel',
            'startDate',
            'endDate',
            'profitLoss',
            'balanceSheet',
        ));
    }

    private function calculateProfitLoss(Carbon $start, Carbon $end): array
    {
        // Revenue
        $revenue = Transaction::whereBetween('transaction_date', [$start, $end])
            ->where('is_paid', 1)
            ->sum('total_price');

        // COGS (HPP)
        $cogs = TransactionDetail::join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->whereBetween('transactions.transaction_date', [$start, $end])
            ->where('transactions.is_paid', 1)
            ->sum(DB::raw('transaction_details.quantity * transaction_details.buying_price'));

        $grossProfit = $revenue - $cogs;

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
        ];
    }

    private function calculateBalanceSheet(Carbon $end): array
    {
        // Assets
        $cashIn = Transaction::where('transaction_date', '<=', $end)
            ->where('is_paid', 1)
            ->sum('total_price');

        $cashOut = ProductPurchase::where('purchase_date', '<=', $end)
            ->where('is_paid', 1)
            ->sum('grand_total');

        $cash = $cashIn - $cashOut;

        $inventoryValue = ProductStock::whereNull('deleted_at')
            ->sum(DB::raw('stock_opname * unit_price'));

        $receivables = Invoice::where('type', Invoice::TYPE_PURCHASE)
            ->sum('debts');

        $totalAssets = $cash + $inventoryValue + $receivables;

        // Liabilities
        $payables = ProductPurchase::where('is_paid', 0)
            ->sum('grand_total');

        $totalLiabilities = $payables;

        // Equity
        $equity = $totalAssets - $totalLiabilities;

        return [
            'assets' => [
                'cash' => $cash,
                'inventory' => $inventoryValue,
                'receivables' => $receivables,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'payables' => $payables,
                'total' => $totalLiabilities,
            ],
            'equity' => $equity,
        ];
    }

    /* ================================================================
       HELPERS
    ================================================================ */
    public function getAllProduct()
    {
        return Product::select('id', 'name')->get();
    }

    public function getAllCategories()
    {
        return ItemCategory::select('id', 'name')->get();
    }

    /* ================================================================
       STATS  — filtered by date range
    ================================================================ */
    private function getStats(Carbon $start, Carbon $end): array
    {
        $now = Carbon::now();

        // Total penjualan dalam range
        $rangeSales = Transaction::whereBetween('transaction_date', [$start, $end])->sum('total_price');

        // Periode sebelumnya (same duration, shifted back)
        $diff = $start->diffInDays($end) + 1;
        $prevStart = $start->copy()->subDays($diff);
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevSales = Transaction::whereBetween('transaction_date', [$prevStart, $prevEnd])->sum('total_price');

        $salesPercentage = $prevSales > 0
            ? round((($rangeSales - $prevSales) / $prevSales) * 100, 1)
            : 0;

        // Penjualan hari ini (selalu tetap)
        $dailySales = Transaction::whereDate('transaction_date', $now->toDateString())->sum('total_price');
        $yesterdaySales = Transaction::whereDate('transaction_date', $now->copy()->subDay()->toDateString())->sum('total_price');
        $dailyPercentage = $yesterdaySales > 0
            ? round((($dailySales - $yesterdaySales) / $yesterdaySales) * 100, 1)
            : 0;

        // Total transaksi dalam range
        $totalTransactions = Transaction::whereBetween('transaction_date', [$start, $end])->count();
        $prevTransactions = Transaction::whereBetween('transaction_date', [$prevStart, $prevEnd])->count();
        $transactionPercentage = $prevTransactions > 0
            ? round((($totalTransactions - $prevTransactions) / $prevTransactions) * 100, 1)
            : 0;

        return [
            'range_sales' => $rangeSales,
            'range_sales_percentage' => abs($salesPercentage),
            'range_sales_trend' => $salesPercentage >= 0 ? 'up' : 'down',

            'daily_sales' => $dailySales,
            'daily_percentage' => abs($dailyPercentage),
            'daily_trend' => $dailyPercentage >= 0 ? 'up' : 'down',

            'total_transactions' => $totalTransactions,
            'transaction_percentage' => abs($transactionPercentage),
            'transaction_trend' => $transactionPercentage >= 0 ? 'up' : 'down',
        ];
    }

    /* ================================================================
       CHART DATA — auto-selects daily / monthly granularity
    ================================================================ */
    private function getChartData(Carbon $start, Carbon $end): array
    {
        $diffDays = $start->diffInDays($end);

        // If range <= 31 days → daily labels.  Otherwise → monthly.
        if ($diffDays <= 31) {
            $data = collect();
            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $total = Transaction::whereDate('transaction_date', $cursor->toDateString())->sum('total_price');
                $data->push([
                    'label' => $cursor->format('d'),
                    'value' => $total,
                ]);
                $cursor->addDay();
            }
        } else {
            $data = collect();
            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $total = Transaction::whereYear('transaction_date', $cursor->year)
                    ->whereMonth('transaction_date', $cursor->month)
                    ->sum('total_price');
                $data->push([
                    'label' => $cursor->format('M'),
                    'value' => $total,
                ]);
                $cursor->addMonth();
            }
        }

        return [
            'labels' => $data->pluck('label')->toArray(),
            'values' => $data->pluck('value')->toArray(),
        ];
    }

    /* ================================================================
       FINANCE REPORTS TABLE — filtered by date range
    ================================================================ */
    private function getFinanceReports(Carbon $start, Carbon $end)
    {
        $sort = request('sort', 'date_new');

        switch ($sort) {
            case 'trx_id_asc':    $orderBy = ['id', 'asc'];
                break;
            case 'trx_id_desc':   $orderBy = ['id', 'desc'];
                break;
            case 'income_in_asc': $orderBy = ['total_price', 'asc'];
                break;
            case 'income_in_desc':$orderBy = ['total_price', 'desc'];
                break;
            case 'date_old':      $orderBy = ['transaction_date', 'asc'];
                break;
            case 'date_new':
            default:              $orderBy = ['transaction_date', 'desc'];
                break;
        }

        $query = Transaction::with(['invoices.customer'])
            ->whereBetween('transaction_date', [$start, $end])
            ->orderBy($orderBy[0], $orderBy[1])
            ->orderBy('id', $orderBy[1] === 'asc' ? 'asc' : 'desc');

        return $query->paginate(25)->withQueryString()->through(function ($t) {
            $r2Invoice = $t->invoices->first();
            $r2Customer = $r2Invoice?->customer;

            return (object) [
                'id' => $t->id,
                'date' => $t->transaction_date,
                'payment_method' => $t->payment_method,
                'discount' => $t->discount,
                'is_paid' => $t->is_paid,
                'total_items_sold' => $t->total_quantity,
                'total_income' => $t->total_price,
                'is_manual' => (bool) $t->is_manual,
                'inv_code' => $r2Invoice?->inv_code,
                'r2_customer' => $r2Customer ? (object) [
                    'id' => $r2Customer->id,
                    'name' => $r2Customer->name,
                    'type' => $r2Customer->type,
                ] : null,
            ];
        });
    }

    /* ================================================================
       SHOW
    ================================================================ */
    public function show(Transaction $transaction)
    {
        $transactionDetails = TransactionDetail::where('transaction_id', $transaction->id)->with('product')->get();

        return view('finance.show', compact('transaction', 'transactionDetails'));
    }

    /* ================================================================
       DOWNLOAD INVOICE PDF (A4)
    ================================================================ */
    public function downloadInvoicePdf(Transaction $transaction)
    {
        $transaction->load('transactionDetails.product');

        $pdf = Pdf::loadView('finance.invoice-pdf', compact('transaction'))
            ->setPaper('A4', 'portrait');

        return $pdf->stream("nota-trx-{$transaction->id}.pdf");
    }

    /* ================================================================
       EDIT — admin (OWNER) only
    ================================================================ */
    public function edit(Transaction $transaction)
    {
        $this->authorizeOwner();

        $transaction->load(['transactionDetails.product', 'invoices']);

        $products = Product::with(['stock' => function ($q) {
            $q->whereNull('deleted_at')->orderBy('created_at', 'asc');
        }])->get()->map(function ($p) {
            $stockSum = $p->stock->sum('stock_opname');
            $firstStock = $p->stock->first();

            return [
                'id' => $p->id,
                'name' => $p->name,
                'price' => (float) ($firstStock?->price_consument ?? 0),
                'stock' => $stockSum,
            ];
        });

        $initialItems = $transaction->transactionDetails->map(fn ($d) => [
            'id' => $d->product_id,
            'name' => $d->product?->name ?? '(produk dihapus)',
            'price' => (float) $d->product_price,
            'qty' => (float) $d->quantity,
            'maxStock' => null,
        ])->values();

        return view('finance.edit', compact('transaction', 'products', 'initialItems'));
    }

    /* ================================================================
       UPDATE — admin (OWNER) only
    ================================================================ */
    public function update(Request $request, Transaction $transaction, TransactionReversalService $service)
    {
        $this->authorizeOwner();

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:products,id',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.qty' => 'required|numeric|min:0.001',
            'totalQty' => 'required|numeric|min:0.001',
            'totalAmount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:Cash,Kredit,QRIS,Transfer',
            'is_paid' => 'required|boolean',
            'cash_received' => 'nullable|numeric|min:0',
            'change_amount' => 'nullable|numeric',
            'transaction_date' => 'nullable|date',
        ]);

        try {
            $service->updateTransaction($transaction, $validated);
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('finance.index')
            ->with('success', 'Transaksi #'.$transaction->id.' berhasil diperbarui.');
    }

    /* ================================================================
       DESTROY — admin (OWNER) only
    ================================================================ */
    public function destroy(Transaction $transaction, TransactionReversalService $service)
    {
        $this->authorizeOwner();

        try {
            $service->reverseAndDelete($transaction);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $redirectTo = request('redirect_to');
        $message = 'Transaksi berhasil dihapus dan stok telah dikembalikan.';

        if ($redirectTo && str_starts_with($redirectTo, url('/'))) {
            return redirect()->to($redirectTo)->with('success', $message);
        }

        return redirect()->route('finance.index')->with('success', $message);
    }

    private function authorizeOwner(): void
    {
        if (! auth()->check() || ! auth()->user()->isOwner()) {
            abort(403, 'Hanya pemilik yang bisa mengubah/menghapus transaksi.');
        }
    }

    /* ================================================================
       MANUAL TRANSACTION — create form (with optional R1/R2 customer)
    ================================================================ */
    public function createManual()
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

        return view('finance.manual-create', compact('categories', 'products'));
    }

    /* ================================================================
       MANUAL TRANSACTION — store (handles guest / R1 / R2, optional stock reduction)
    ================================================================ */
    public function storeManual(Request $request)
    {
        $this->normalizeDecimalInput($request);

        $validated = $request->validate([
            'customer_kind' => 'required|in:guest,r1,r2',
            'customer_id' => 'required_unless:customer_kind,guest|nullable|integer|exists:customers,id',
            'reduce_stock' => 'required|boolean',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:products,id',
            'items.*.price' => 'required|numeric|min:0|decimal:0,3',
            'items.*.qty' => 'required|numeric|min:0.001|decimal:0,3',
            'items.*.basePrice' => 'nullable|numeric|min:0',
            'totalQty' => 'required|numeric|min:0.001',
            'totalAmount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|decimal:0,3',
            'payment_method' => 'required|in:Cash,Kredit,QRIS,Transfer',
            'is_paid' => 'required|boolean',
            'cash_received' => 'nullable|numeric|min:0',
            'change_amount' => 'nullable|numeric',
            'created_at' => 'required|date|before_or_equal:now',
            'note' => 'nullable|string|max:1000',
        ]);

        $customerKind = $validated['customer_kind'];
        $customer = null;
        if ($customerKind !== 'guest') {
            $customer = Customer::where('id', $validated['customer_id'])
                ->where('type', $customerKind)
                ->first();
            if (! $customer) {
                return redirect()->back()->withInput()->withErrors([
                    'customer_id' => 'Pelanggan tidak ditemukan atau tipe tidak cocok.',
                ]);
            }
        }

        $math = app(DecimalMathService::class);

        $reduceStock = (bool) $validated['reduce_stock'];
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

        if ($reduceStock) {
            $stockErrors = [];
            foreach ($preparedItems as $idx => $item) {
                $available = (string) ProductStock::where('product_id', $item['id'])
                    ->whereNull('deleted_at')
                    ->sum('stock_opname');
                if ($math->compare($available, $item['qty']) < 0) {
                    $name = Product::where('id', $item['id'])->value('name') ?? "Produk #{$item['id']}";
                    $stockErrors["items.$idx.qty"] = "Stok '{$name}' hanya {$available}, tidak cukup untuk {$item['qty']}.";
                }
            }
            if (! empty($stockErrors)) {
                return redirect()->back()->withInput()->withErrors($stockErrors);
            }
        }

        $cashReceived = null;
        $changeAmount = null;
        if ($validated['payment_method'] === 'Cash') {
            $cashReceived = isset($validated['cash_received']) ? $math->round($validated['cash_received']) : null;
            $changeAmount = isset($validated['change_amount']) ? $math->round($validated['change_amount']) : null;

            if ($isPaid && $cashReceived !== null && $math->compare($cashReceived, $totalAmount) < 0) {
                return redirect()->back()->withInput()->withErrors([
                    'cash_received' => 'Uang diterima kurang dari total transaksi.',
                ]);
            }
        }

        $now = Carbon::now();
        $transactionDate = Carbon::parse($validated['created_at'])
            ->setTimezone(config('app.timezone'))
            ->setTime($now->hour, $now->minute, $now->second);

        try {
            DB::transaction(function () use (
                $customer, $reduceStock, $preparedItems, $totalAmount, $totalQty,
                $discount, $isPaid, $cashReceived, $changeAmount, $transactionDate, $validated, $math
            ) {
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
                    $productStock = null;
                    $buyingPrice = '0.000';
                    if ($reduceStock) {
                        $productStock = ProductStock::where('product_id', $item['id'])
                            ->whereNull('deleted_at')
                            ->where('stock_opname', '>', 0)
                            ->orderBy('created_at', 'asc')
                            ->first();
                        $buyingPrice = $productStock ? $math->round($productStock->unit_price) : '0.000';
                    }

                    $transaction->transactionDetails()->create([
                        'product_id' => $item['id'],
                        'product_stock_id' => $productStock?->id,
                        'product_price' => $item['price'],
                        'buying_price' => $buyingPrice,
                        'quantity' => $item['qty'],
                        'total_price' => $item['lineTotal'],
                        'created_at' => $transactionDate,
                        'updated_at' => $transactionDate,
                    ]);

                    if ($reduceStock && $productStock) {
                        $productStock->decrement('stock_opname', (float) $item['qty']);
                    }
                }

                if ($customer) {
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
                                ['customer_id' => $customer->id, 'product_id' => $item['id']],
                                ['custom_price' => $manualPrice],
                            );
                        }
                    }
                }
            });

            $redirectParams = $this->buildRangeFilterFor($transactionDate);

            return redirect()->route('finance.index', $redirectParams)
                ->with('success', 'Transaksi manual berhasil disimpan.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->withErrors([
                'general' => 'Terjadi kesalahan saat menyimpan transaksi: '.$e->getMessage(),
            ]);
        }
    }

    /* ================================================================
       DOWNLOAD PDF
    ================================================================ */
    public function download(Request $request)
    {
        $request->validate([
            'range_type' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format_time' => 'required|in:harian,bulanan,tahunan',
            'download_by' => 'required|in:category,product',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:item_categories,id',
        ]);

        Carbon::setLocale('id');

        $endDate = Carbon::now();
        $startDate = null;

        $rangeMap = [
            '7days' => fn () => Carbon::now()->subDays(7),
            '1month' => fn () => Carbon::now()->subMonth(),
            '3months' => fn () => Carbon::now()->subMonths(3),
            '6months' => fn () => Carbon::now()->subMonths(6),
            '1year' => fn () => Carbon::now()->subYear(),
        ];

        if ($request->range_type === 'custom') {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
        } else {
            $startDate = $rangeMap[$request->range_type]();
        }

        $periodSelect = match ($request->format_time) {
            'harian' => DB::raw('DATE(transactions.transaction_date) as period'),
            'bulanan' => DB::raw('DATE_FORMAT(transactions.transaction_date, "%Y-%m") as period'),
            'tahunan' => DB::raw('YEAR(transactions.transaction_date) as period'),
        };

        $query = DB::table('transactions')
            ->join('transaction_details', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('products', 'products.id', '=', 'transaction_details.product_id')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'products.item_category_id')
            ->where('transactions.is_paid', 1)
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate]);

        if ($request->download_by === 'product') {
            $query
                ->whereIn('products.id', $request->product_ids ?? [])
                ->select($periodSelect, 'products.id as product_id', 'products.name as product_name', DB::raw('SUM(transaction_details.quantity) as total_qty'), DB::raw('SUM(transaction_details.total_price) as total_sales'))
                ->groupBy('period', 'products.id', 'products.name');
        } else {
            $query
                ->whereIn('item_categories.id', $request->category_ids ?? [])
                ->select($periodSelect, 'item_categories.id as category_id', 'item_categories.name as category_name', DB::raw('SUM(transaction_details.quantity) as total_qty'), DB::raw('SUM(transaction_details.total_price) as total_sales'))
                ->groupBy('period', 'item_categories.id', 'item_categories.name');
        }

        $data = $query->orderBy('period')->get();

        $data = $data->map(function ($row) use ($request) {
            if ($request->format_time === 'bulanan') {
                $row->period = strtoupper(Carbon::createFromFormat('Y-m', $row->period)->translatedFormat('F, Y'));
            } elseif ($request->format_time === 'harian') {
                $row->period = Carbon::parse($row->period)->translatedFormat('d F Y');
            }

            return $row;
        });

        $columnCount = $request->download_by === 'product'
            ? $data->pluck('product_id')->unique()->count()
            : $data->pluck('category_id')->unique()->count();

        $isLandscape = $columnCount <= 10;

        if ($isLandscape) {
            $columns = $request->download_by === 'product'
                ? $data->pluck('product_name', 'product_id')->unique()
                : $data->pluck('category_name', 'category_id')->unique();

            $totalQty = [];
            foreach ($columns as $id => $name) {
                $totalQty[$id] = $data->filter(fn ($r) => $request->download_by === 'product' ? $r->product_id == $id : $r->category_id == $id)->sum('total_qty');
            }

            $pivot = $data->groupBy('period')->map(function ($rows) use ($columns, $request) {
                $out = [];
                foreach ($columns as $id => $name) {
                    $out[$id] = $rows->filter(fn ($r) => $request->download_by === 'product' ? $r->product_id == $id : $r->category_id == $id)->sum('total_sales');
                }

                return $out;
            });
        } else {
            $columns = [];
            $pivot = [];
            $totalQty = [];
        }

        $totalSales = [];
        foreach ($columns as $id => $name) {
            $totalSales[$id] = $data
                ->filter(fn ($r) => $request->download_by === 'product' ? $r->product_id == $id : $r->category_id == $id)
                ->sum('total_sales');
        }

        $grandTotalQty = array_sum($totalQty);
        $grandTotalSales = array_sum($totalSales);
        $data = $data->sortBy('period')->groupBy('period');

        $pdf = Pdf::loadView('finance.report', [
            'data' => $data,
            'pivot' => $pivot,
            'columns' => $columns,
            'totalSales' => $totalSales,
            'grandTotalQty' => $grandTotalQty,
            'grandTotalSales' => $grandTotalSales,
            'totalQty' => $totalQty,
            'isLandscape' => $isLandscape,
            'downloadBy' => $request->download_by,
            'startDate' => $startDate->translatedFormat('d F Y'),
            'endDate' => $endDate->translatedFormat('d F Y'),
        ]);

        if ($isLandscape) {
            $pdf->setPaper('A4', 'landscape');
        }

        return $pdf->download('laporan-penjualan.pdf');
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
