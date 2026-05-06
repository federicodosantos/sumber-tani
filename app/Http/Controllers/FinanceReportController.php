<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\ProductStock;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\TransactionReversalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    /**
     * Resolve start & end dates from the range_filter parameter.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}  [startDate, endDate, rangeKey]
     */
    private function resolveDateRange(Request $request): array
    {
        $now = Carbon::now();
        $rangeKey = $request->input('range_filter', 'this_month');

        switch ($rangeKey) {
            case 'this_week':
                $start = $now->copy()->startOfWeek();
                $end   = $now->copy()->endOfWeek();
                break;

            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end   = $now->copy()->subMonth()->endOfMonth();
                break;

            case 'this_quarter':
                $start = $now->copy()->firstOfQuarter();
                $end   = $now->copy()->lastOfQuarter()->endOfDay();
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
                $end   = $now->copy()->endOfMonth();
                break;
        }

        return [$start, $end, $rangeKey];
    }

    /**
     * Human-readable label for the chosen range.
     */
    private function getRangeLabel(string $key, Carbon $start, Carbon $end): string
    {
        return match ($key) {
            'this_week'    => 'Minggu Ini',
            'this_month'   => 'Bulan Ini',
            'last_month'   => 'Bulan Lalu',
            'this_quarter' => 'Kuartal Ini',
            'custom'       => $start->format('d M Y') . ' - ' . $end->format('d M Y'),
            default        => 'Bulan Ini',
        };
    }

    /* ================================================================
       INDEX
    ================================================================ */
    public function index(Request $request)
    {
        [$startDate, $endDate, $rangeKey] = $this->resolveDateRange($request);

        $transactionFilter = $request->input('transaction_filter', 'daily');

        $stats         = $this->getStats($startDate, $endDate);
        $chartData     = $this->getChartData($startDate, $endDate);
        $financeReports = $this->getFinanceReports($startDate, $endDate);
        $products      = $this->getAllProduct();
        $categories    = $this->getAllCategories();

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
        $revenue = Transaction::whereBetween('created_at', [$start, $end])
            ->where('is_paid', 1)
            ->sum('total_price');

        // COGS (HPP)
        $cogs = TransactionDetail::join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->whereBetween('transactions.created_at', [$start, $end])
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
        $cashIn = Transaction::where('created_at', '<=', $end)
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
        $rangeSales = Transaction::whereBetween('created_at', [$start, $end])->sum('total_price');

        // Periode sebelumnya (same duration, shifted back)
        $diff = $start->diffInDays($end) + 1;
        $prevStart = $start->copy()->subDays($diff);
        $prevEnd   = $start->copy()->subDay()->endOfDay();
        $prevSales = Transaction::whereBetween('created_at', [$prevStart, $prevEnd])->sum('total_price');

        $salesPercentage = $prevSales > 0
            ? round((($rangeSales - $prevSales) / $prevSales) * 100, 1)
            : 0;

        // Penjualan hari ini (selalu tetap)
        $dailySales     = Transaction::whereDate('created_at', $now->toDateString())->sum('total_price');
        $yesterdaySales = Transaction::whereDate('created_at', $now->copy()->subDay()->toDateString())->sum('total_price');
        $dailyPercentage = $yesterdaySales > 0
            ? round((($dailySales - $yesterdaySales) / $yesterdaySales) * 100, 1)
            : 0;

        // Total transaksi dalam range
        $totalTransactions = Transaction::whereBetween('created_at', [$start, $end])->count();
        $prevTransactions  = Transaction::whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $transactionPercentage = $prevTransactions > 0
            ? round((($totalTransactions - $prevTransactions) / $prevTransactions) * 100, 1)
            : 0;

        return [
            'range_sales'              => $rangeSales,
            'range_sales_percentage'   => abs($salesPercentage),
            'range_sales_trend'        => $salesPercentage >= 0 ? 'up' : 'down',

            'daily_sales'              => $dailySales,
            'daily_percentage'         => abs($dailyPercentage),
            'daily_trend'              => $dailyPercentage >= 0 ? 'up' : 'down',

            'total_transactions'       => $totalTransactions,
            'transaction_percentage'   => abs($transactionPercentage),
            'transaction_trend'        => $transactionPercentage >= 0 ? 'up' : 'down',
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
                $total = Transaction::whereDate('created_at', $cursor->toDateString())->sum('total_price');
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
                $total = Transaction::whereYear('created_at', $cursor->year)
                    ->whereMonth('created_at', $cursor->month)
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
            case 'trx_id_asc':    $orderBy = ['id', 'asc']; break;
            case 'trx_id_desc':   $orderBy = ['id', 'desc']; break;
            case 'income_in_asc': $orderBy = ['total_price', 'asc']; break;
            case 'income_in_desc':$orderBy = ['total_price', 'desc']; break;
            case 'date_old':      $orderBy = ['created_at', 'asc']; break;
            case 'date_new':
            default:              $orderBy = ['created_at', 'desc']; break;
        }

        $query = Transaction::whereBetween('created_at', [$start, $end])
            ->orderBy($orderBy[0], $orderBy[1]);

        return $query->paginate(25)->withQueryString()->through(function ($t) {
            return (object) [
                'id'              => $t->id,
                'date'            => $t->created_at,
                'payment_method'  => $t->payment_method,
                'discount'        => $t->discount,
                'is_paid'         => $t->is_paid,
                'total_items_sold'=> $t->total_quantity,
                'total_income'    => $t->total_price,
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
                'price' => $firstStock?->price_consument ?? 0,
                'stock' => $stockSum,
            ];
        });

        $initialItems = $transaction->transactionDetails->map(fn ($d) => [
            'id'       => $d->product_id,
            'name'     => $d->product?->name ?? '(produk dihapus)',
            'price'    => (float) $d->product_price,
            'qty'      => (int) $d->quantity,
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
            'items.*.qty' => 'required|integer|min:1',
            'totalQty' => 'required|numeric|min:1',
            'totalAmount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:Cash,Kredit,QRIS,Transfer',
            'is_paid' => 'required|boolean',
            'cash_received' => 'nullable|numeric|min:0',
            'change_amount' => 'nullable|numeric',
            'created_at' => 'nullable|date',
        ]);

        try {
            $service->updateTransaction($transaction, $validated);
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('finance.index')
            ->with('success', 'Transaksi #' . $transaction->id . ' berhasil diperbarui.');
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

        return redirect()->route('finance.index')
            ->with('success', 'Transaksi berhasil dihapus dan stok telah dikembalikan.');
    }

    private function authorizeOwner(): void
    {
        if (! auth()->check() || ! auth()->user()->isOwner()) {
            abort(403, 'Hanya pemilik yang bisa mengubah/menghapus transaksi.');
        }
    }

    /* ================================================================
       DOWNLOAD PDF
    ================================================================ */
    public function download(Request $request)
    {
        $request->validate([
            'range_type'    => 'required|string',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'format_time'   => 'required|in:harian,bulanan,tahunan',
            'download_by'   => 'required|in:category,product',
            'product_ids'   => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'category_ids'  => 'nullable|array',
            'category_ids.*'=> 'exists:item_categories,id',
        ]);

        Carbon::setLocale('id');

        $endDate   = Carbon::now();
        $startDate = null;

        $rangeMap = [
            '7days'   => fn() => Carbon::now()->subDays(7),
            '1month'  => fn() => Carbon::now()->subMonth(),
            '3months' => fn() => Carbon::now()->subMonths(3),
            '6months' => fn() => Carbon::now()->subMonths(6),
            '1year'   => fn() => Carbon::now()->subYear(),
        ];

        if ($request->range_type === 'custom') {
            $startDate = Carbon::parse($request->start_date);
            $endDate   = Carbon::parse($request->end_date);
        } else {
            $startDate = $rangeMap[$request->range_type]();
        }

        $periodSelect = match ($request->format_time) {
            'harian'  => DB::raw('DATE(transactions.created_at) as period'),
            'bulanan' => DB::raw('DATE_FORMAT(transactions.created_at, "%Y-%m") as period'),
            'tahunan' => DB::raw('YEAR(transactions.created_at) as period'),
        };

        $query = DB::table('transactions')
            ->join('transaction_details', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('products', 'products.id', '=', 'transaction_details.product_id')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'products.item_category_id')
            ->where('transactions.is_paid', 1)
            ->whereBetween('transactions.created_at', [$startDate, $endDate]);

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
                $totalQty[$id] = $data->filter(fn($r) => $request->download_by === 'product' ? $r->product_id == $id : $r->category_id == $id)->sum('total_qty');
            }

            $pivot = $data->groupBy('period')->map(function ($rows) use ($columns, $request) {
                $out = [];
                foreach ($columns as $id => $name) {
                    $out[$id] = $rows->filter(fn($r) => $request->download_by === 'product' ? $r->product_id == $id : $r->category_id == $id)->sum('total_sales');
                }
                return $out;
            });
        } else {
            $columns  = [];
            $pivot    = [];
            $totalQty = [];
        }

        $totalSales = [];
        foreach ($columns as $id => $name) {
            $totalSales[$id] = $data
                ->filter(fn($r) => $request->download_by === 'product' ? $r->product_id == $id : $r->category_id == $id)
                ->sum('total_sales');
        }

        $grandTotalQty   = array_sum($totalQty);
        $grandTotalSales = array_sum($totalSales);
        $data = $data->sortBy('period')->groupBy('period');

        $pdf = Pdf::loadView('finance.report', [
            'data'            => $data,
            'pivot'           => $pivot,
            'columns'         => $columns,
            'totalSales'      => $totalSales,
            'grandTotalQty'   => $grandTotalQty,
            'grandTotalSales' => $grandTotalSales,
            'totalQty'        => $totalQty,
            'isLandscape'     => $isLandscape,
            'downloadBy'      => $request->download_by,
            'startDate'       => $startDate->translatedFormat('d F Y'),
            'endDate'         => $endDate->translatedFormat('d F Y'),
        ]);

        if ($isLandscape) {
            $pdf->setPaper('A4', 'landscape');
        }

        return $pdf->download('laporan-penjualan.pdf');
    }
}
