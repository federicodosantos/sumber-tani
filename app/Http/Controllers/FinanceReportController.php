<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    public function index(Request $request)
    {
        $transactionFilter = $request->get('transaction_filter', 'daily'); // daily, weekly, monthly, yearly

        $stats = $this->getStats($transactionFilter);
        $chartData = $this->getChartData($transactionFilter);
        $financeReports = $this->getFinanceReports();
        $products = $this->getAllProduct();
        $categories = $this->getAllCategories();

        return view('finance.index', compact('stats', 'chartData', 'financeReports', 'transactionFilter', 'products', 'categories'));
    }

    public function getAllProduct()
    {
        return Product::select('id', 'name')->get();
    }

    public function getAllCategories()
    {
        return ItemCategory::select('id', 'name')->get();
    }

    private function getStats($transactionFilter)
    {
        $now = Carbon::now();

        // Daily Sales (today) - Selalu tetap
        $dailySales = Transaction::whereDate('created_at', $now->toDateString())->sum('total_price');

        // Daily Sales Yesterday (for comparison)
        $yesterdaySales = Transaction::whereDate('created_at', $now->copy()->subDay()->toDateString())->sum('total_price');

        $dailyPercentage = $yesterdaySales > 0 ? round((($dailySales - $yesterdaySales) / $yesterdaySales) * 100, 1) : 0;

        // Monthly Sales (current month) - Selalu tetap
        $monthlySales = Transaction::whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)->sum('total_price');

        // Last Month Sales (for comparison)
        $lastMonthSales = Transaction::whereYear('created_at', $now->copy()->subMonth()->year)
            ->whereMonth('created_at', $now->copy()->subMonth()->month)
            ->sum('total_price');

        $monthlyPercentage = $lastMonthSales > 0 ? round((($monthlySales - $lastMonthSales) / $lastMonthSales) * 100, 1) : 0;

        // Total Transactions - BERDASARKAN FILTER
        switch ($transactionFilter) {
            case 'weekly':
                $totalTransactions = Transaction::whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->count();

                $lastPeriodTransactions = Transaction::whereBetween('created_at', [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()])->count();
                break;

            case 'monthly':
                $totalTransactions = Transaction::whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)->count();

                $lastPeriodTransactions = Transaction::whereYear('created_at', $now->copy()->subMonth()->year)
                    ->whereMonth('created_at', $now->copy()->subMonth()->month)
                    ->count();
                break;

            case 'yearly':
                $totalTransactions = Transaction::whereYear('created_at', $now->year)->count();

                $lastPeriodTransactions = Transaction::whereYear('created_at', $now->year - 1)->count();
                break;

            default:
                // daily
                $totalTransactions = Transaction::whereDate('created_at', $now->toDateString())->count();

                $lastPeriodTransactions = Transaction::whereDate('created_at', $now->copy()->subDay()->toDateString())->count();
        }

        $transactionPercentage = $lastPeriodTransactions > 0 ? round((($totalTransactions - $lastPeriodTransactions) / $lastPeriodTransactions) * 100, 1) : 0;

        return [
            'daily_sales' => $dailySales,
            'daily_percentage' => abs($dailyPercentage),
            'daily_trend' => $dailyPercentage >= 0 ? 'up' : 'down',

            'monthly_sales' => $monthlySales,
            'monthly_percentage' => abs($monthlyPercentage),
            'monthly_trend' => $monthlyPercentage >= 0 ? 'up' : 'down',

            'total_transactions' => $totalTransactions,
            'transaction_percentage' => abs($transactionPercentage),
            'transaction_trend' => $transactionPercentage >= 0 ? 'up' : 'down',
            'transaction_filter_label' => $this->getFilterLabel($transactionFilter),
        ];
    }

    private function getChartData($filter)
    {
        $now = Carbon::now();

        switch ($filter) {
            case 'weekly':
                // Last 7 days
                $dates = collect();
                for ($i = 6; $i >= 0; $i--) {
                    $date = $now->copy()->subDays($i);
                    $dates->push($date);
                }

                $data = $dates->map(function ($date) {
                    $total = Transaction::whereDate('created_at', $date->toDateString())->sum('total_price');

                    return [
                        'label' => $date->format('D'),
                        'value' => $total,
                    ];
                });
                break;

            case 'monthly':
                // Days in current month
                $daysInMonth = $now->daysInMonth;
                $data = collect();

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = Carbon::create($now->year, $now->month, $day);
                    $total = Transaction::whereDate('created_at', $date->toDateString())->sum('total_price');

                    $data->push([
                        'label' => (string) $day,
                        'value' => $total,
                    ]);
                }
                break;

            case 'yearly':
                // 12 months
                $data = collect();

                for ($month = 1; $month <= 12; $month++) {
                    $total = Transaction::whereYear('created_at', $now->year)->whereMonth('created_at', $month)->sum('total_price');

                    $data->push([
                        'label' => Carbon::create($now->year, $month, 1)->format('M'),
                        'value' => $total,
                    ]);
                }
                break;

            default:
                // Default ke weekly jika tidak ada yang match
                return $this->getChartData('weekly');
        }

        return [
            'labels' => $data->pluck('label')->toArray(),
            'values' => $data->pluck('value')->toArray(),
        ];
    }

    private function getFinanceReports()
    {
        $sort = request('sort', 'date_new');

        switch ($sort) {
            case 'trx_id_asc':
                $orderBy = ['id', 'asc'];
                break;

            case 'trx_id_desc':
                $orderBy = ['id', 'desc'];
                break;

            case 'income_in_asc':
                $orderBy = ['total_price', 'asc'];
                break;

            case 'income_in_desc':
                $orderBy = ['total_price', 'desc'];
                break;

            case 'date_old':
                $orderBy = ['created_at', 'asc'];
                break;

            case 'date_new':
            default:
                $orderBy = ['created_at', 'desc'];
                break;
        }

        $query = Transaction::orderBy($orderBy[0], $orderBy[1]);

        return $query->paginate(25)->through(function ($t) {
            return (object) [
                'id' => $t->id,
                'date' => $t->created_at,
                'payment_method' => $t->payment_method,
                'discount' => $t->discount,
                'is_paid' => $t->is_paid,
                'total_items_sold' => $t->total_quantity,
                'total_income' => $t->total_price,
            ];
        });
    }

    private function getFilterLabel($filter)
    {
        return match ($filter) {
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan',
            'yearly' => 'Tahunan',
            default => 'Harian',
        };
    }

    public function show(Transaction $transaction)
    {
        $transactionDetails = TransactionDetail::where('transaction_id', $transaction->id)->with('product')->get();
        return view('finance.show', compact('transaction', 'transactionDetails'));
    }

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
            '7days' => fn() => Carbon::now()->subDays(7),
            '1month' => fn() => Carbon::now()->subMonth(),
            '3months' => fn() => Carbon::now()->subMonths(3),
            '6months' => fn() => Carbon::now()->subMonths(6),
            '1year' => fn() => Carbon::now()->subYear(),
        ];

        if ($request->range_type === 'custom') {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
        } else {
            $startDate = $rangeMap[$request->range_type]();
        }

        $periodSelect = match ($request->format_time) {
            'harian' => DB::raw('DATE(transactions.created_at) as period'),
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

        $columnCount = $request->download_by === 'product' ? $data->pluck('product_id')->unique()->count() : $data->pluck('category_id')->unique()->count();

        $isLandscape = $columnCount <= 10;

        if ($isLandscape) {
            // kolom (produk / kategori)
            $columns = $request->download_by === 'product' ? $data->pluck('product_name', 'product_id')->unique() : $data->pluck('category_name', 'category_id')->unique();

            // TOTAL QTY (AKUMULASI SEMUA BULAN)
            $totalQty = [];
            foreach ($columns as $id => $name) {
                $totalQty[$id] = $data->filter(fn($r) => $request->download_by === 'product' ? $r->product_id == $id : $r->category_id == $id)->sum('total_qty');
            }

            // TOTAL SALES PER BULAN (FOCUS KE PENDAPATAN)
            $pivot = $data->groupBy('period')->map(function ($rows) use ($columns, $request) {
                $out = [];
                foreach ($columns as $id => $name) {
                    $out[$id] = $rows->filter(fn($r) => $request->download_by === 'product' ? $r->product_id == $id : $r->category_id == $id)->sum('total_sales');
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
                ->filter(fn ($r) =>
                    $request->download_by === 'product'
                        ? $r->product_id == $id
                        : $r->category_id == $id
                )
                ->sum('total_sales');
        }

        $grandTotalQty   = array_sum($totalQty);
        $grandTotalSales = array_sum($totalSales);
        $data = $data
        ->sortBy('period')
        ->groupBy('period');

        $pdf = Pdf::loadView('finance.report', [
            'data' => $data,
            'pivot' => $pivot,
            'columns' => $columns,
            'totalSales'  => $totalSales,
            'grandTotalQty'     => $grandTotalQty,     
            'grandTotalSales'   => $grandTotalSales,   
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
}
