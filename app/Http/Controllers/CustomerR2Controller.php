<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerR2Controller extends Controller
{
    /**
     * Display a listing of R-2 customers.
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        switch ($request->get('sort')) {
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

        return view('pelanggan-r2.index', compact('customers'));
    }

    /**
     * Show the form for creating a new R-2 customer.
     */
    public function create()
    {
        return view('pelanggan-r2.create');
    }

    /**
     * Store a newly created R-2 customer.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
            'address' => 'required|string|max:255',
        ]);

        try {
            Customer::create($validated);

            return redirect()->route('pelanggan-r2.index')->with('success', 'Pelanggan R2 berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan saat menambahkan pelanggan.']);
        }
    }

    /**
     * Display R-2 customer detail with debt info and invoices.
     */
    public function show(Customer $customer)
    {
        $customer->load(['invoices.transaction']);

        $totalDebt = $customer->invoices->sum('debts');

        return view('pelanggan-r2.show', compact('customer', 'totalDebt'));
    }

    /**
     * Show the debt payment form for a customer.
     */
    public function payDebt(Customer $customer)
    {
        $totalDebt = $customer->invoices()->where('debts', '>', 0)->sum('debts');

        return view('pelanggan-r2.payment', compact('customer', 'totalDebt'));
    }

    /**
     * Process debt payment using FIFO logic.
     */
    public function processPayment(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $amount = (float) $validated['amount'];

        // Check total debt
        $totalDebt = $customer->invoices()->where('debts', '>', 0)->sum('debts');

        if ($amount > $totalDebt) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['amount' => 'Nominal pembayaran melebihi total hutang (Rp ' . number_format($totalDebt, 0, ',', '.') . ').']);
        }

        try {
            DB::transaction(function () use ($customer, &$amount) {
                // FIFO: get unpaid invoices ordered by oldest first
                $invoices = $customer->invoices()
                    ->where('debts', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($invoices as $invoice) {
                    if ($amount <= 0) break;

                    if ($amount >= $invoice->debts) {
                        // Payment covers this invoice fully
                        $amount -= $invoice->debts;
                        $invoice->update(['debts' => 0]);
                    } else {
                        // Partial payment
                        $invoice->update(['debts' => $invoice->debts - $amount]);
                        $amount = 0;
                    }
                }
            });

            return redirect()
                ->route('pelanggan-r2.show', $customer->id)
                ->with('success', 'Pembayaran hutang berhasil diproses.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan saat memproses pembayaran.']);
        }
    }

    /**
     * Search R-2 customers (JSON API for cashier modal).
     */
    public function search(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name', 'asc')->limit(20)->get(['id', 'name', 'phone_number', 'address']);

        return response()->json($customers);
    }
}
