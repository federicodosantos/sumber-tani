<?php

namespace App\Http\Controllers;

use App\Models\ProductPurchase;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProductPurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ProductPurchase::query();

        // 📅 FILTER TANGGAL (RANGE)
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('purchase_date', [$request->date_from, $request->date_to]);
        }

        // 📅 FILTER SATU TANGGAL (kalau cuma isi salah satu)
        elseif ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }

        // 🔃 SORTING
        switch ($request->input('sort')) {
            case 'purchase_date_asc':
                $query->orderBy('purchase_date', 'asc');
                break;

            case 'purchase_date_desc':
                $query->orderBy('purchase_date', 'desc');
                break;

            case 'method_asc':
                $query->orderBy('payment_method', 'asc');
                break;

            case 'total_asc':
                $query->orderBy('grand_total', 'asc');
                break;

            case 'total_desc':
                $query->orderBy('grand_total', 'desc');
                break;

            case 'paid':
                $query->where('is_paid', true)->orderBy('purchase_date', 'desc');
                break;

            case 'unpaid':
                $query->where('is_paid', false)->orderBy('purchase_date', 'desc');
                break;

            default:
                $query->orderBy('purchase_date', 'desc');
                break;
        }

        $purchases = $query->paginate(10)->withQueryString();

        return view('product-purchase.index', compact('purchases'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product-purchase.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'purchase_date' => ['required', 'date'],
                'ppn' => ['nullable', 'numeric', 'min:0'],
                'discount' => ['nullable', 'numeric', 'min:0'],
                'method' => ['required', 'integer', 'in:0,12'],
                'manual_grand_total' => ['nullable', 'numeric', 'min:0'],
                'products' => ['required', 'array', 'min:1'],
                'products.*.code' => ['required', 'string', 'max:100'],
                'products.*.item' => ['required', 'string', 'max:150'],
                'products.*.price' => ['required'],
                'products.*.quantity' => ['required', 'integer', 'min:1'],
                'products.*.unit' => ['required', 'string', 'max:50'],
            ]);

            $paymentMethod = (int) $validated['method'] === 0 ? 'cash' : 'credit';
            $isPaid = $paymentMethod === 'cash' ? true : $request->boolean('isPaid');

            $items = collect($validated['products'])->map(function ($item) {
                $price = $this->parseNumber($item['price']);
                $qty = (int) $item['quantity'];

                return [
                    'product_code' => $item['code'],
                    'product_name' => $item['item'],
                    'unit' => $item['unit'],
                    'price' => $price,
                    'quantity' => $qty,
                    'subtotal' => $price * $qty,
                ];
            });

            $subtotal = $items->sum('subtotal');
            $discountPercent = (float) ($validated['discount'] ?? 0);
            $discountValue = $subtotal * ($discountPercent / 100);
            $afterDiscount = $subtotal - $discountValue;
            $ppnPercent = (float) ($validated['ppn'] ?? 0);
            $ppnValue = $afterDiscount * ($ppnPercent / 100);

            $grandTotal = !empty($validated['manual_grand_total']) ? (float) $validated['manual_grand_total'] : $afterDiscount + $ppnValue;

            DB::transaction(function () use ($validated, $items, $subtotal, $discountPercent, $discountValue, $ppnPercent, $ppnValue, $grandTotal, $paymentMethod, $isPaid) {
                $purchase = ProductPurchase::create([
                    'purchase_date' => $validated['purchase_date'],
                    'total_items' => $items->sum('quantity'),
                    'subtotal' => $subtotal,
                    'discount_percent' => $discountPercent,
                    'discount_value' => $discountValue,
                    'ppn_percent' => $ppnPercent,
                    'ppn_value' => $ppnValue,
                    'grand_total' => $grandTotal,
                    'payment_method' => $paymentMethod,
                    'is_paid' => $isPaid,
                ]);

                $items->each(fn($item) => $purchase->details()->create($item));
            });

            return redirect()->route('purchase.index')->with('success', 'Pembelian produk berhasil disimpan.');
        } catch (\Throwable $e) {
            dd([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductPurchase $productPurchase)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductPurchase $purchase)
    {
        $purchase->load('details');

        return view('product-purchase.edit', compact('purchase'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductPurchase $purchase)
    {
        try {
            $validated = $request->validate([
                'purchase_date' => ['required', 'date'],
                'ppn' => ['nullable', 'numeric', 'min:0'],
                'discount' => ['nullable', 'numeric', 'min:0'],
                'method' => ['required', 'integer', 'in:0,12'],
                'manual_grand_total' => ['nullable', 'numeric', 'min:0'], // 👈 TAMBAH INI
                'products' => ['required', 'array', 'min:1'],
                'products.*.code' => ['required', 'string'],
                'products.*.item' => ['required', 'string'],
                'products.*.price' => ['required'],
                'products.*.quantity' => ['required', 'integer', 'min:1'],
                'products.*.unit' => ['required'],
            ]);

            $paymentMethod = (int) $validated['method'] === 0 ? 'cash' : 'credit';
            $isPaid = $paymentMethod === 'cash' ? true : $request->boolean('isPaid');

            $items = collect($validated['products'])->map(function ($item) {
                $price = $this->parseNumber($item['price']);
                $qty = (int) $item['quantity'];

                return [
                    'product_code' => $item['code'],
                    'product_name' => $item['item'],
                    'unit' => $item['unit'],
                    'price' => $price,
                    'quantity' => $qty,
                    'subtotal' => $price * $qty,
                ];
            });

            $subtotal = $items->sum('subtotal');
            $discountPercent = (float) ($validated['discount'] ?? 0);
            $discountValue = $subtotal * ($discountPercent / 100);
            $afterDiscount = $subtotal - $discountValue;
            $ppnPercent = (float) ($validated['ppn'] ?? 0);
            $ppnValue = $afterDiscount * ($ppnPercent / 100);

            // 👇 GUNAKAN MANUAL PRICE JIKA ADA
            $grandTotal = !empty($validated['manual_grand_total']) ? (float) $validated['manual_grand_total'] : $afterDiscount + $ppnValue;

            DB::transaction(function () use ($purchase, $validated, $items, $subtotal, $discountPercent, $discountValue, $ppnPercent, $ppnValue, $grandTotal, $paymentMethod, $isPaid) {
                $purchase->update([
                    'purchase_date' => $validated['purchase_date'],
                    'total_items' => $items->sum('quantity'),
                    'subtotal' => $subtotal,
                    'discount_percent' => $discountPercent,
                    'discount_value' => $discountValue,
                    'ppn_percent' => $ppnPercent,
                    'ppn_value' => $ppnValue,
                    'grand_total' => $grandTotal,
                    'payment_method' => $paymentMethod,
                    'is_paid' => $isPaid,
                ]);

                // reset details
                $purchase->details()->delete();
                $items->each(fn($item) => $purchase->details()->create($item));
            });

            return redirect()->route('purchase.index')->with('success', 'Pembelian berhasil diperbarui.');
        } catch (\Throwable $e) {
            dd([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductPurchase $purchase)
    {
        ProductPurchase::destroy($purchase->id);

        return redirect()->route('purchase.index')->with('success', 'Pembelian berhasil dihapus.');
    }

    private function parseNumber(string $value): float
    {
        $clean = preg_replace('/[^0-9,.,-]/', '', $value);
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);

        return (float) $clean;
    }
}
