<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DebtPayment;
use App\Models\DebtPaymentDetail;
use App\Models\CustomerProductPrice;
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

        return view('customer-r2.index', compact('customers'));
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
            'phone_number' => 'required|string|max:15',
            'address' => 'required|string|max:255',
        ]);

        try {
            Customer::create($validated);

            return redirect()->route('customer-r2.index')->with('success', 'Pelanggan R2 berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan saat menambahkan pelanggan.']);
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
